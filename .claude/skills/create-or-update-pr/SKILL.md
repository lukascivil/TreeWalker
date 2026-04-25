---
name: create-or-update-pr
description: Creates or updates the GitHub Pull Request for the current branch, generating title and body from git log and diff.
---

Your task is to create or update the GitHub Pull Request for the current branch.

## Steps

1. Run these commands in parallel to gather context:
   - `git log --oneline $(git merge-base HEAD master)..HEAD` — commits on this branch
   - `git diff $(git merge-base HEAD master)..HEAD --stat` — changed files summary
   - `gh pr view --json number,title,body,state 2>/dev/null` — existing PR, if any

2. Analyze all commits and changed files to understand the intent of the branch.

3. Build the PR title:
   - Follow conventional commits: `type(scope): short description`
   - Use the most significant type across all commits (`feat` > `fix` > `refactor` > `chore`)
   - If there is only one commit and its message is already a good title, use it directly
   - Keep it under 72 characters
   - Do not add a period at the end

4. Build the PR body using this template:

```
## Summary
- <bullet 1>
- <bullet 2>

## Changes
- <file or area>: <what changed and why>

## Test plan
- [ ] <step to verify>
```

- Summary: 2–4 bullets on _what_ and _why_, not _how_
- Changes: one line per meaningful file group or area changed
- Test plan: concrete steps a reviewer can follow to verify the PR

5. If no PR exists for this branch, create one targeting `master`:

```bash
gh pr create --title "..." --body "$(cat <<'EOF'
...
EOF
)"
```

6. If a PR already exists, update its title and body:

```bash
gh pr edit --title "..." --body "$(cat <<'EOF'
...
EOF
)"
```

7. Output the PR URL at the end.

## Rules

- Never push commits — only create or edit the PR metadata
- If the branch has no commits ahead of master, tell the user and stop
- Use the `gh` CLI for all GitHub operations
- Always use a HEREDOC (`<<'EOF'`) to pass the body so newlines are preserved
