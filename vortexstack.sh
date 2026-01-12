#!/bin/bash
# VortexStack: subfinder → httpx → nuclei
# Safe background execution + real-time results + kill switch
#
# 📌 Authors:
#    🐺 w0lfsn1p3r  (Original idea & user)
#    🤖 Gemini (Implementation & improvements)
#
# 🚀 Version: 1.0.2

set -euo pipefail

TOOL_NAME="VortexStack"
TOOL_VERSION="1.0.2"
TOOL_AUTHOR="🐺 w0lfsn1p3r & 🤖 Gemini"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
RESET='\033[0m'

# === Lolcat Check ===
if command -v lolcat &>/dev/null; then
    L_CAT="lolcat"
else
    L_CAT="cat"
fi

# Emojis per process
EMO_RECON_START=("🎯" "🛡️" "🌟" "🚀" "💫")
EMO_SUBFINDER=("🔍" "👀" "📡" "🕵️‍♂️" "🗂️")
EMO_HTTPX=("🌊" "⚡" "☀️" "🔥" "💧")
EMO_NUCLEI=("💥" "💢" "🔥" "⚠️" "🔎")
EMO_SUCCESS=("✅" "🎉" "💯" "🏆" "🌈")

# Spinner emojis
SPINNER_EMOJIS=( "🎉" "🔥" "🌟" "💫" "🚀" "⚡" "💥" "🌈" "🛡️" "🎯"
                 "💣" "🌊" "☀️" "💧" "🌙" "⭐" "🕹️" "🎮" "🧨" "🌪️"
                 "🌀" "🌸" "🌺" "🌻" "🍁" "🍂" "🌼" "🌷" "🌹" "🍀"
                 "🌏" "🌍" "🌎" "🌐" "🌑" "🌒" "🌓" "🌔" "🌕" "🌖"
                 "🌗" "🌘" "🌙" "☁️" "⛅" "🌤️" "🌥️" "🌦️" "🌧️" "⛈️"
                 "🌩️" "🌨️" "❄️" "☃️" "⛄" "💨" "💧" "💦" "🌊" "🔥"
                 "💡" "🔦" "🔋" "🪐" "🌌" "🌠" "🌟" "✨" "⚡" "☄️"
                 "🪄" "🎇" "🎆" "🧨" "🎃" "🎄" "🎁" "🎈" "🎉" "🎊"
                 "🏆" "🥇" "🥈" "🥉" "🏅" "🎖️" "🏵️" "🎗️" "💎" "💍"
                 "🪙" "💰" "🪜" "🛠️" "⚙️" "🔧" "🔨" "🪓" "⛏️" "🛡️" )

LOG_DIR="$HOME/recon-logs"
mkdir -p "$LOG_DIR"

# === Domain Validation ===
validate_domain() {
    local domain=$1
    # Standard Regex for FQDN validation
    local domain_regex="^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$"
    if [[ "$domain" =~ $domain_regex ]]; then
        return 0
    else
        return 1
    fi
}

# === ASCII Banner ===
ascii_banner() {
cat << "EOF" | $L_CAT
oooooo     oooo                        .                        .oooooo..o     .                        oooo       
 `888.     .8'                        .o8                        d8P'    `Y8   .o8                        `888       
  `888.   .8'    .ooooo.  oooo d8b .o888oo  .ooooo.  oooo    ooo Y88bo.      .o888oo  .oooo.    .ooooo.   888  oooo 
   `888. .8'    d88' `88b `888""8P   888   d88' `88b  `88b..8P'    `"Y8888o.    888   `P  )88b  d88' `"Y8  888 .8P'  
    `888.8'     888   888  888       888   888ooo888    Y888'          `"Y88b   888    .oP"888  888        888888.   
     `888'      888   888  888       888 . 888    .o  .o8"'88b   oo     .d8P   888 . d8(  888  888   .o8  888 `88b. 
      `8'       `Y8bod8P' d888b      "888" `Y8bod8P' o88'   888o 8""88888P'    "888" `Y888""8o `Y8bod8P' o888o o888o
EOF
echo -e "                     🚀 $TOOL_NAME v$TOOL_VERSION — Subfinder → Httpx → Nuclei 🚀" | $L_CAT
echo -e "                     👤 Authors: $TOOL_AUTHOR" | $L_CAT
echo ""
}

# === Auto-detect Nuclei Templates ===
NUCLEI_TEMPLATES=""
POSSIBLE_TEMPLATE_DIRS=("$HOME/nuclei-templates" "$HOME/.local/nuclei-templates" "$HOME/.nuclei-templates" "/root/nuclei-templates" "/opt/nuclei-templates")
for dir in "${POSSIBLE_TEMPLATE_DIRS[@]}"; do
    if [[ -d "$dir" ]]; then NUCLEI_TEMPLATES="$dir"; break; fi
done

# === Help & Version ===
show_version() { echo "$TOOL_NAME v$TOOL_VERSION (Authors: $TOOL_AUTHOR)"; }
show_help() {
cat << EOF
$TOOL_NAME - Minimal Automated Recon Stack

Usage:
  $0 <domain>          Run recon for a single domain
  $0 -l domains.txt    Run recon for multiple domains (line-separated)
  $0 -h, --help        Show this help menu
  $0 --version         Show version
  $0 --kill            Kill all running $TOOL_NAME jobs

Logs saved in: $LOG_DIR
EOF
}

# === Spinner Function ===
spinner() {
    local pid=$1 delay=0.1 idx=0 count=${#SPINNER_EMOJIS[@]}
    while kill -0 "$pid" 2>/dev/null; do
        printf " [%s]  " "${SPINNER_EMOJIS[$idx]}"
        idx=$(( (idx + 1) % count ))
        sleep $delay
        printf "\b\b\b\b\b\b\b"
    done
}

# === Colorize Nuclei Output ===
colorize_nuclei() {
    while IFS= read -r line; do
        if [[ "$line" =~ Critical ]]; then echo -e "${RED}$(shuf -n1 -e "${EMO_NUCLEI[@]}") $line${RESET}"
        elif [[ "$line" =~ High ]]; then echo -e "${MAGENTA}$(shuf -n1 -e "${EMO_NUCLEI[@]}") $line${RESET}"
        elif [[ "$line" =~ Medium ]]; then echo -e "${YELLOW}$(shuf -n1 -e "${EMO_NUCLEI[@]}") $line${RESET}"
        elif [[ "$line" =~ Low ]]; then echo -e "${CYAN}$(shuf -n1 -e "${EMO_NUCLEI[@]}") $line${RESET}"
        else echo -e "$(shuf -n1 -e "${EMO_NUCLEI[@]}") $line"; fi
    done
}

# === Run Command with Spinner ===
run_command_with_spinner() {
    local cmd=("$@")
    "${cmd[@]}" & local pid=$!
    spinner $pid
    wait $pid
}

# === Main Recon Function ===
run_recon() {
    local DOMAIN=$1
    if ! validate_domain "$DOMAIN"; then
        echo -e "${RED}[!] Error: '$DOMAIN' is not a valid domain. Skipping...${RESET}"
        return
    fi

    local TMPFILE="subs-${DOMAIN}-$$.tmp"
    echo -e "${GREEN}[+] $(shuf -n1 -e "${EMO_RECON_START[@]}") Starting recon for $DOMAIN${RESET}"

    echo -e "${YELLOW}[*] $(shuf -n1 -e "${EMO_SUBFINDER[@]}") Finding subdomains...${RESET}"
    run_command_with_spinner subfinder -d "$DOMAIN" -silent -o "$TMPFILE"

    echo -e "${YELLOW}[*] $(shuf -n1 -e "${EMO_HTTPX[@]}") Probing alive hosts...${RESET}"
    run_command_with_spinner httpx -l "$TMPFILE" -silent -o "${DOMAIN}.txt"

    echo -e "${YELLOW}[*] $(shuf -n1 -e "${EMO_NUCLEI[@]}") Running nuclei scans...${RESET}"
    nuclei -l "${DOMAIN}.txt" -t "$NUCLEI_TEMPLATES" -silent | tee >(colorize_nuclei) | tee "${DOMAIN}.nuclei.txt"

    rm -f "$TMPFILE"
    echo -e "${GREEN}[+] $(shuf -n1 -e "${EMO_SUCCESS[@]}") Recon finished for $DOMAIN${RESET}\n"
}

# === Kill Switch ===
if [[ $# -eq 1 && "$1" == "--kill" ]]; then
    echo -e "${RED}[!] Stopping processes...${RESET}"
    pkill -f "subfinder" || true; pkill -f "httpx" || true; pkill -f "nuclei" || true
    exit 0
fi

# === Input Argument Handling ===
if [[ $# -eq 0 ]]; then show_help; exit 1; fi

case "$1" in
    -h|--help) ascii_banner; show_help; exit 0 ;;
    --version) show_version; exit 0 ;;
    -l)
        if [[ -z "${2:-}" || ! -f "$2" ]]; then
            echo -e "${RED}[!] Error: File '$2' not found.${RESET}"
            exit 1
        fi
        ;;
esac

# === Background Launch Logic ===
if [[ -z "${RECON_BG_RUNNING:-}" ]]; then
    export RECON_BG_RUNNING=1
    LOG_FILE="$LOG_DIR/recon-$(date +%F_%H-%M).log"
    ascii_banner
    echo -e "${GREEN}[+] 🎯 Starting background session. Logs: $LOG_FILE${RESET}"
    setsid bash "$0" "$@" | tee -a "$LOG_FILE" &
    disown
    exit 0
fi

trap "" SIGINT

# === Final Execution Loop ===
if [[ "$1" == "-l" ]]; then
    while read -r domain; do
        [[ -z "$domain" || "$domain" =~ ^# ]] && continue
        run_recon "$domain"
    done < "$2"
else
    run_recon "$1"
fi
