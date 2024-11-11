#!/bin/bash

# This script reinstalls WordPress for a specified user's sites.
# It will:
# 1. Prompt for the username.
# 2. Switch to the user's account.
# 3. Change to the user's home directory.
# 4. Traverse one directory deep in the home directory.
# 5. Check for the existence of wp-config.php in each directory.
# 6. If wp-config.php exists, reinstall WordPress using:
#    wp core download --skip-content --force
# 7. If wp-config.php does not exist, skip the directory.
# 
# Usage:
# Run the script and enter the username when prompted.

# Prompt for the username
read -p "Enter the username: " username

# Switch to the specified user and execute the commands
su -s /bin/bash - "$username" <<'EOF'
cd ~
for dir in */; do
  if [ -d "$dir" ]; then
    if [ -f "$dir/wp-config.php" ]; then
      echo "wp-config.php found in $dir. Reinstalling WordPress."
      wp core download --skip-content --force --path="$dir"
    else
      echo "wp-config.php not found in $dir. Skipping."
    fi
  fi
done
EOF
