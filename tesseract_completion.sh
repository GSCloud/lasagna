#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

_tesseract_completions()
{
    local cur prev opts
    COMPREPLY=()
    cur="${COMP_WORDS[COMP_CWORD]}"
    prev="${COMP_WORDS[COMP_CWORD-1]}"

    opts="clearcache clearci clearlogs cleartemp demo doctor local refresh unit version versionjson"

    if [[ ${cur} == * ]] ; then
        COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
        return 0
    fi
}

complete -F _tesseract_completions app
