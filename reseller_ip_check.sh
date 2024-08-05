#!/bin/bash

# Function to display help text
show_help() {
  echo "Usage: $0"
  echo
  echo "This script retrieves the domains for each user under a specified cPanel reseller,"
  echo "checks their A records, and verifies if the IP address matches the server's IP address."
  echo
  echo "You will be prompted to enter the reseller username once the script runs."
  echo
  echo "Example:"
  echo "  ./check_a_records.sh"
}

# Check for help argument
if [ "$1" == "--help" ] || [ "$1" == "-h" ]; then
  show_help
  exit 0
fi

# Prompt for the reseller username
read -p "Enter the reseller username: " reseller_username

# Exit if no username is provided
if [ -z "$reseller_username" ]; then
  echo "Error: Reseller username cannot be empty."
  show_help
  exit 1
fi

# Retrieve the expected IP address
expected_ip=$(host `hostname` | awk '{print $4}')

# Retrieve usernames of accounts managed by the reseller
reseller_usernames=$(uapi --user="$reseller_username" Resellers list_accounts | grep "user:" | awk '{print $2}')

# Exit if no accounts are found
if [ -z "$reseller_usernames" ]; then
  echo "No reseller accounts found for $reseller_username."
  exit 1
fi

# Loop through each username and check the A record for each domain
for user in $reseller_usernames; do
  domains=$(perl -F: -lane 'if ($F[1] =~ /'"$user"'/) {print $F[0]}' /etc/userdomains)
  for domain in $domains; do
    # Check the A record for the domain
    a_record=$(dig +short "$domain" A)
    if [ "$a_record" != "$expected_ip" ]; then
      echo "$domain: $a_record (wrong IP)"
    else
      echo "$domain: $a_record"
    fi
  done
done
