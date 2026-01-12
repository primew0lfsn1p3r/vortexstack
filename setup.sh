#!/bin/bash
# ~/vortexstack/setup.sh
# Version: 1.5.0 - Global Edition
# 📌 Author: 🐺 w0lfsn1p3r & 🤖 Gemini

set -euo pipefail

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
RESET='\033[0m'

# === Lolcat Check ===
if command -v lolcat &>/dev/null; then
    L_CAT="lolcat"
else
    L_CAT="cat"
fi

# === Banner ===
ascii_banner() {
cat << "EOF" | $L_CAT
 oooooo     oooo                         .                         .oooooo..o     .                         oooo       
 `888.     .8'                        .o8                         d8P'    `Y8   .o8                         `888       
  `888.   .8'     .ooooo.  oooo d8b .o888oo  .ooooo.  oooo    ooo Y88bo.      .o888oo  .oooo.    .ooooo.   888  oooo 
   `888. .8'     d88' `88b `888""8P   888   d88' `88b  `88b..8P'    `"Y8888o.    888   `P  )88b  d88' `"Y8  888 .8P' 
    `888.8'      888   888  888       888   888ooo888    Y888'          `"Y88b   888    .oP"888  888        888888.  
     `888'       888   888  888       888 . 888    .o  .o8"'88b    oo     .d8P   888 . d8(  888  888   .o8  888 `88b. 
      `8'        `Y8bod8P' d888b      "888" `Y8bod8P' o88'    888o 8""88888P'    "888" `Y888""8o `Y8bod8P' o888o o888o
EOF
echo -e "                      🛠️  VortexStack Installer v1.5.0 🛠️" | $L_CAT
echo ""
}

# 1. Dependency Check
echo -e "${YELLOW}[*] Checking system dependencies...${RESET}"
for pkg in wget git curl tar lolcat sudo; do
    if ! command -v "$pkg" &> /dev/null; then
        echo -e "${YELLOW}[!] Installing missing dependency: $pkg...${RESET}"
        sudo apt-get update && sudo apt-get install -y "$pkg"
        [[ "$pkg" == "lolcat" ]] && L_CAT="lolcat"
    fi
done

ascii_banner

# 2. Environment Setup
export GOROOT=/usr/local/go
export GOPATH=$HOME/go
export PATH=$PATH:$GOROOT/bin:$GOPATH/bin

# 3. Install Go
if command -v go &>/dev/null; then
    echo -e "${GREEN}[✔] Golang is already installed.${RESET}"
else
    echo -e "${YELLOW}[*] Installing Golang 1.21.6...${RESET}"
    ARCH=$(dpkg --print-architecture)
    wget "https://golang.org/dl/go1.21.6.linux-${ARCH}.tar.gz" -O /tmp/go.tar.gz
    sudo rm -rf /usr/local/go
    sudo tar -C /usr/local -xzf /tmp/go.tar.gz
    rm /tmp/go.tar.gz
fi

# 4. Configure Shell RC
SHELL_RC="$HOME/.bashrc"
[[ "$SHELL" == */zsh ]] && SHELL_RC="$HOME/.zshrc"

if ! grep -q "GOPATH" "$SHELL_RC"; then
    echo -e "${YELLOW}[*] Adding Golang paths to $SHELL_RC...${RESET}"
    echo -e "\n# VortexStack Golang Config\nexport GOROOT=/usr/local/go\nexport GOPATH=\$HOME/go\nexport PATH=\$PATH:\$GOROOT/bin:\$GOPATH/bin" >> "$SHELL_RC"
fi

# 5. Install pdtm & Tools
echo -e "${YELLOW}[*] Installing ProjectDiscovery tools...${RESET}"
/usr/local/go/bin/go install -v github.com/projectdiscovery/pdtm/cmd/pdtm@latest
$HOME/go/bin/pdtm -install subfinder,httpx,nuclei

# 6. Symlink PD Tools to /usr/bin
echo -e "${YELLOW}[*] Syncing PD tools to /usr/bin for global access...${RESET}"
PDTM_PATH="$HOME/.pdtm/go/bin"
if [ -d "$PDTM_PATH" ]; then
    for bin in "$PDTM_PATH"/*; do
        sudo ln -sf "$bin" "/usr/bin/$(basename "$bin")"
    done
fi

# 7. Global VortexStack Command
echo -e "${YELLOW}[*] Setting up 'vortexstack' global command...${RESET}"
CURRENT_DIR=$(pwd)
if [ -f "$CURRENT_DIR/vortexstack.sh" ]; then
    chmod +x "$CURRENT_DIR/vortexstack.sh"
    sudo ln -sf "$CURRENT_DIR/vortexstack.sh" /usr/local/bin/vortexstack
    echo -e "${GREEN}[✔] Global access enabled: type 'vortexstack' from anywhere.${RESET}"
else
    echo -e "${RED}[!] vortexstack.sh not found in $(pwd). Skipping global symlink.${RESET}"
fi

# 8. Templates Update
echo -e "${YELLOW}[*] Updating Nuclei templates...${RESET}"
nuclei -ut -silent

echo -e "--------------------------------------------------" | $L_CAT
echo -e "[+] Setup Finished! Run: source $SHELL_RC" | $L_CAT
echo -e "--------------------------------------------------" | $L_CAT
