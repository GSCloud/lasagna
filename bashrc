umask 022
export PAGER=cat

alias ll='ls -l'
alias la='ls -lA'
alias ..='cd ..'
alias ...='cd ../..'

alias app='/var/www/cli.sh'

if [ -f /var/www/tesseract_completion.sh ]; then
    . /var/www/tesseract_completion.sh
fi
