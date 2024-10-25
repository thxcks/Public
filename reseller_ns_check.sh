#!/bin/bash

# Function to display help text
show_help() {
  echo "Usage: $0"
  echo
  echo "This script retrieves the domains for each user under a specified cPanel reseller"
  echo "and outputs their NS records."
  echo
  echo "You will be prompted to enter the reseller username once the script runs."
  echo
  echo "Example:"
  echo "  ./reseller_ns_check.sh"
}

# Check for help argument
if [ "$1" == "--help" ] || [ "$1" == "-h" ]; then
  show_help
  exit 0
fi

# Prompt for the reseller username
echo -n "Enter the reseller username: "
read reseller_username

# Exit if no username is provided
if [ -z "$reseller_username" ]; then
  echo "Error: Reseller username cannot be empty."
  show_help
  exit 1
fi

# Retrieve usernames of accounts managed by the reseller
reseller_usernames=$(uapi --user="$reseller_username" Resellers list_accounts | grep "user:" | awk '{print $2}')

# Exit if no accounts are found
if [ -z "$reseller_usernames" ]; then
  echo "No reseller accounts found for $reseller_username."
  exit 1
fi

# Loop through each username and output the NS records for each domain
for user in $reseller_usernames; do
  domains=$(perl -F: -lane 'if ($F[1] =~ /'"$user"'/) {print $F[0]}' /etc/userdomains)
  for domain in $domains; do
    # Retrieve the NS records for the domain
    ns_records=$(dig +short "$domain" NS)
    echo "$domain: $ns_records"
  done
done
