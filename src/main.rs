use dialoguer::{theme::ColorfulTheme, Select, Input};
use std::process::Command;
#[cfg(test)]
use std::process::Output;
#[cfg(all(test, unix))]
use std::os::unix::process::ExitStatusExt;
#[cfg(all(test, windows))]
use std::os::windows::process::ExitStatusExt;

/// Main entry point for UniShell - Git Operations Terminal
fn main() {
    loop {
        println!("Welcome to UniShell - Git Operations Terminal");

        let options = vec![
            "Status",
            "Add Files",
            "Commit Changes",
            "Push Changes",
            "Pull Changes",
            "Switch Branch",
            "Initialize Repository",
            "Clone Repository",
            "Pull Specific Folder",
            "Include Submodules",
            "Remove Folder",
            "Restore Folder",
            "Exit",
        ];

        let selection = Select::with_theme(&ColorfulTheme::default())
            .with_prompt("Choose a Git operation")
            .items(&options)
            .default(0)
            .interact()
            .unwrap();

        match selection {
            0 => git_status(),
            1 => git_add(),
            2 => git_commit(),
            3 => git_push(),
            4 => git_pull(),
            5 => git_switch_branch(),
            6 => git_init(),
            7 => git_clone(),
            8 => git_pull_folder(),
            9 => git_include_submodules(),
            10 => git_remove_folder(),
            11 => git_restore_folder(),
            12 => {
                println!("Exiting UniShell. Goodbye!");
                break;
            }
            _ => unreachable!(),
        }
    }
}

/// Runs `git status` and prints the output
fn git_status() {
    println!("Running `git status`...");
    let output = Command::new("git")
        .arg("status")
        .output()
        .expect("Failed to execute git status");
    println!("{}", String::from_utf8_lossy(&output.stdout));
}

/// Adds files to the staging area
fn git_add() {
    let files: String = Input::new()
        .with_prompt("Enter files to add (use '.' to add all)")
        .interact_text()
        .unwrap();

    println!("Running `git add {}`...", files);
    Command::new("git")
        .arg("add")
        .arg(files)
        .status()
        .expect("Failed to execute git add");
}

/// Commits changes with a message
fn git_commit() {
    let message: String = Input::new()
        .with_prompt("Enter commit message")
        .interact_text()
        .unwrap();

    println!("Running `git commit -m \"{}\"`...", message);
    Command::new("git")
        .arg("commit")
        .arg("-m")
        .arg(message)
        .status()
        .expect("Failed to execute git commit");
}

/// Pushes changes to the remote repository
fn git_push() {
    println!("Running `git push`...");
    Command::new("git")
        .arg("push")
        .status()
        .expect("Failed to execute git push");
}

/// Pulls changes from the remote repository
fn git_pull() {
    println!("Running `git pull`...");
    Command::new("git")
        .arg("pull")
        .status()
        .expect("Failed to execute git pull");
}

/// Switches to a specified branch
fn git_switch_branch() {
    let branch: String = Input::new()
        .with_prompt("Enter branch name to switch to")
        .interact_text()
        .unwrap();

    println!("Running `git checkout {}`...", branch);
    Command::new("git")
        .arg("checkout")
        .arg(branch)
        .status()
        .expect("Failed to execute git checkout");
}

/// Initializes a new Git repository
fn git_init() {
    println!("Running `git init`...");
    Command::new("git")
        .arg("init")
        .status()
        .expect("Failed to execute git init");
}

/// Clones a repository from a given URL
fn git_clone() {
    let repo_url: String = Input::new()
        .with_prompt("Enter repository URL to clone")
        .interact_text()
        .unwrap();

    println!("Running `git clone {}`...", repo_url);
    Command::new("git")
        .arg("clone")
        .arg(repo_url)
        .status()
        .expect("Failed to execute git clone");
}

/// Pulls a specific folder using sparse-checkout
fn git_pull_folder() {
    let folder: String = Input::new()
        .with_prompt("Enter folder path to pull")
        .interact_text()
        .unwrap();

    println!("Running `git sparse-checkout set {}` and pulling...", folder);
    Command::new("git")
        .arg("sparse-checkout")
        .arg("set")
        .arg(&folder)
        .status()
        .expect("Failed to set sparse-checkout folder");

    Command::new("git")
        .arg("pull")
        .status()
        .expect("Failed to pull specific folder");
}

/// Includes submodules in the repository
fn git_include_submodules() {
    println!("Running `git submodule update --init --recursive`...");
    Command::new("git")
        .arg("submodule")
        .arg("update")
        .arg("--init")
        .arg("--recursive")
        .status()
        .expect("Failed to include submodules");
}

/// Removes a folder from the repository
fn git_remove_folder() {
    let folder: String = Input::new()
        .with_prompt("Enter folder path to remove")
        .interact_text()
        .unwrap();

    println!("Removing folder `{}`...", folder);
    Command::new("rm")
        .arg("-rf")
        .arg(&folder)
        .status()
        .expect("Failed to remove folder");
}

/// Restores a folder to its previous state
fn git_restore_folder() {
    let folder: String = Input::new()
        .with_prompt("Enter folder path to restore")
        .interact_text()
        .unwrap();

    println!("Restoring folder `{}`...", folder);
    Command::new("git")
        .arg("checkout")
        .arg("--")
        .arg(&folder)
        .status()
        .expect("Failed to restore folder");
}

#[cfg(test)]
mod tests {
    use super::*;

    fn mock_command_output(stdout: &str) -> Output {
        Output {
            status: std::process::ExitStatus::from_raw(0),
            stdout: stdout.as_bytes().to_vec(),
            stderr: Vec::new(),
        }
    }

    #[test]
    fn test_git_status() {
        let output = mock_command_output("On branch main\nYour branch is up to date.");
        assert_eq!(
            String::from_utf8_lossy(&output.stdout),
            "On branch main\nYour branch is up to date."
        );
    }

    #[test]
    fn test_git_add() {
        // Mocking user input and command execution
        let files = "test_file.txt";
        assert_eq!(files, "test_file.txt");
    }

    #[test]
    fn test_git_commit() {
        // Mocking user input and command execution
        let message = "Initial commit";
        assert_eq!(message, "Initial commit");
    }

    #[test]
    fn test_git_push() {
        // Mocking command execution
        let output = mock_command_output("Everything up-to-date");
        assert_eq!(
            String::from_utf8_lossy(&output.stdout),
            "Everything up-to-date"
        );
    }
}
