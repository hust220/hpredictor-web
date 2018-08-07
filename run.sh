#set -e
#trap 'case $? in
#        139) echo "segfault occurred";;
#      esac' EXIT
#trap 'echo segfault' SIGCHLD
trap 'if [[ $? -eq 139 ]]; then echo "segfault !"; fi' SIGSEGV

source /var/www/html/local/env.sh

run() {
/var/www/html/hpredictor/extensions/DE-DL-HA.linux /var/www/html/hpredictor/$1.pdb >aa.txt
}

run $1

