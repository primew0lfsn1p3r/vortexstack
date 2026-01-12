#!/bin/bash
# ~/setup.sh
# Version: 1.5.0 - Rainbow Edition (lolcat integration)
# 📌 Author: 🐺 w0lfsn1p3r & 🤖 Gemini

set -euo pipefail

# Colors for standard messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
RESET='\033[0m'

# === Lolcat Check & Function ===
if command -v lolcat &>/dev/null; then
    L_CAT="lolcat"
else
    L_CAT="cat"
fi

# === Rainbow ASCII Banner ===
ascii_banner() {
cat << "EOF" | $L_CAT
 oooooo     oooo                        .                          .oooooo..o     .                       oooo       
 `888.     .8'                       .o8                         d8P'    `Y8   .o8                       `888       
  `888.   .8'    .ooooo.  oooo d8b .o888oo  .ooooo.  oooo    ooo Y88bo.      .o888oo  .oooo.    .ooooo.   888  oooo 
   `888. .8'    d88' `88b `888""8P   888   d88' `88b  `88b..8P'   `"Y8888o.    888   `P  )88b  d88' `"Y8  888 .8P'  
    `888.8'     888   888  888       888   888ooo888    Y888'         `"Y88b   888    .oP"888  888        888888.   
     `888'      888   888  888       888 . 888    .o  .o8"'88b   oo     .d8P   888 . d8(  888  888   .o8  888 `88b. 
      `8'       `Y8bod8P' d888b      "888" `Y8bod8P' o88'   888o 8""88888P'    "888" `Y888""8o `Y8bod8P' o888o o888o
EOF
echo -e "                     🛠️  VortexStack Installer v1.5.0 🛠️" | $L_CAT
echo ""
}

# 1. System Dependency Check (Includes lolcat)
echo -e "${YELLOW}[*] Checking system dependencies...${RESET}"
for pkg in wget git curl tar lolcat; do
    if ! command -v "$pkg" &> /dev/null; then
        echo -e "${YELLOW}[!] Installing missing dependency: $pkg...${RESET}"
        sudo apt-get update && sudo apt-get install -y "$pkg"
        # Re-check lolcat after installation to enable rainbow mode
        [[ "$pkg" == "lolcat" ]] && L_CAT="lolcat"
    fi
done

ascii_banner

# 2. Environment variables for this session
export GOROOT=/usr/local/go
export GOPATH=$HOME/go
export PATH=$PATH:$GOROOT/bin:$GOPATH/bin

# 3. Check for Go
if command -v go &>/dev/null && [[ "$(go version)" == *"go1.2"* ]]; then
    echo -e "${GREEN}[✔] Golang is already installed.${RESET}"
else
    echo -e "${YELLOW}[*] Installing Golang...${RESET}"
    GO_VER="1.21.6"
    ARCH=$(dpkg --print-architecture)
    wget "https://golang.org/dl/go${GO_VER}.linux-${ARCH}.tar.gz" -O /tmp/go.tar.gz
    sudo rm -rf /usr/local/go
    sudo tar -C /usr/local -xzf /tmp/go.tar.gz
    rm /tmp/go.tar.gz
fi

# 4. Check Shell Path Configuration
SHELL_RC="$HOME/.bashrc"
[[ "$SHELL" == */zsh ]] && SHELL_RC="$HOME/.zshrc"

if grep -q "GOROOT" "$SHELL_RC"; then
    echo -e "${GREEN}[✔] Shell paths already configured.${RESET}"
else
    echo -e "${YELLOW}[*] Configuring paths in $SHELL_RC...${RESET}"
    cat << 'EOF' >> "$SHELL_RC"
# ReconStack - Golang Environment
export GOROOT=/usr/local/go
export GOPATH=$HOME/go
export PATH=$PATH:$GOROOT/bin:$GOPATH/bin
EOF
fi

# 5. Install pdtm
if [ -f "$GOPATH/bin/pdtm" ]; then
    echo -e "${GREEN}[✔] pdtm is already installed.${RESET}"
else
    echo -e "${YELLOW}[*] Installing pdtm...${RESET}"
    /usr/local/go/bin/go install -v github.com/projectdiscovery/pdtm/cmd/pdtm@latest
fi

# 6. Check/Install subfinder, httpx, nuclei
for tool in subfinder httpx nuclei; do
    if command -v "$tool" &>/dev/null; then
        echo -e "${GREEN}[✔] $tool is ready.${RESET}"
    else
        echo -e "${YELLOW}[*] Installing $tool...${RESET}"
        "$GOPATH/bin/pdtm" -install "$tool"
    fi
done

# 7. Sync Symlinks
echo -e "${YELLOW}[*] Syncing symlinks to /usr/bin/...${RESET}"
PDTM_DIR="$HOME/.pdtm/go/bin"
if [ -d "$PDTM_DIR" ]; then
    for bin in "$PDTM_DIR"/*; do
        if [ -x "$bin" ]; then
            sudo ln -sf "$bin" "/usr/bin/$(basename "$bin")"
        fi
    done
fi

# 8. Nuclei Templates Update
echo -e "${YELLOW}[*] Updating Nuclei templates...${RESET}"
nuclei -ut -silent

echo -e "--------------------------------------------------" | $L_CAT
echo -e "[+] VortexStack Setup Complete!" | $L_CAT
echo -e "--------------------------------------------------" | $L_CAT
