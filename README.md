<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=gradient&customColorList=10,2,3,4,5,6,7,8,9&height=220&section=header&text=VortexStack&fontSize=50&animation=fadeIn&fontAlignY=40" />
</p>
<div align="center">
  
# 🌪️ VortexStack

[![Version](https://img.shields.io/badge/version-1.5.0-blueviolet)](https://github.com/primew0lfsn1p3r/vortexstack)
[![Author](https://img.shields.io/badge/author-w0lfsn1p3r-orange)](https://github.com/primew0lfsn1p3r)
[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/primew0lfsn1p3r/vortexstack/blob/main/LICENSE)

**VortexStack** is a high-speed, automated reconnaissance framework designed for bug bounty hunters and security researchers. It streamlines the transition from **subdomain discovery** to **vulnerability scanning** with a focus on background execution, real-time logging, and a professional terminal interface.

---
## 📦 Technical Stack

| Phase | Tool | Function |
| :--- | :--- | :--- |
| **Discovery** | `subfinder` | Passive & Active subdomain enumeration |
| **Probing** | `httpx` | Live host validation & title/status code grabbing |
| **Vulnerability** | `nuclei` | Template-based scanning (CVEs, exposures, OOB) |
| **Management** | `pdtm` | Tool versioning and automated updates |
| **UX/UI** | `lolcat` | ANSI rainbow styling & dynamic spinners |

## 🚀 Key Features

* **The Golden Trio:** Seamlessly chains `subfinder` → `httpx` → `nuclei`.
* **Stealth Background Mode:** Automatically detaches from the terminal using `setsid`, allowing you to close your session while the scan continues.
* **Kill Switch:** Stop all background scans and child processes instantly with `./vortexstack.sh --kill`.
* **Visual UX:** Features a dynamic 100-emoji spinner and `lolcat` rainbow integration.
* **Intelligent Logic:** Auto-sorts unique domains and filters alive hosts to prevent WAF bans and redundant requests.
* **OOB Ready:** Full support for Out-of-Band (OOB) interaction testing via Nuclei's Interactsh.

---

## 🛠️ Automated Installation

Set up the entire environment (Golang, pdtm, and all tools) with one command:

```bash
git clone https://github.com/primew0lfsn1p3r/vortexstack.git && cd vortexstack && chmod +x setup.sh vortexstack.sh && ./setup.sh && source ~/.bashrc

```

> **Note:** After installation, run `source ~/.bashrc` (or `~/.zshrc`) to refresh your environment.

---

## 📖 Usage Guide

### Single Target

```bash
./vortexstack.sh example.com

```

### Multiple Targets (List)

```bash
./vortexstack.sh -l domains.txt

```

### Management

```bash
# Stop all running VortexStack processes
./vortexstack.sh --kill

# View version info
./vortexstack.sh --version

```

---

## 📂 Data & Logging

Scans are organized to be persistent and easy to review:

* **Background Logs:** `~/recon-logs/recon-YYYY-MM-DD_HH-MM.log`
* **Alive Hosts:** `domain.com.txt`
* **Nuclei Results:** `domain.com.nuclei.txt`

---

## ⚙️ Logic Workflow

1. **Discovery:** `subfinder` gathers subdomains from multiple passive sources.
2. **Validation:** `httpx` probes for active web services (HTTP/HTTPS).
3. **Vulnerability Scan:** `nuclei` executes 9,000+ templates to find CVEs and misconfigurations.
4. **Reporting:** Results are colorized and saved to a dedicated report file.

---

## 🛡️ Credits

* **Lead Developer:** [ 🐺 PRIME w0lfsn1p3r 🐺
](https://www.google.com/url?sa=E&source=gmail&q=https://github.com/primew0lfsn1p3r)
* **Implementation Partner:** Gemini AI

---

## ⚖️ Disclaimer

This tool is intended for **ethical security testing** and authorized bug bounty programs only. Do not use it against targets without explicit permission. The authors are not responsible for any misuse or damage.

