#!/bin/sh

confirmCommand() {
    _CONFIRMCOMMAND='n';
    read -p "Confirm (Y)es,(n)o (def:'$_CONFIRMCOMMAND'): " CONFIRMCOMMAND
    CONFIRMCOMMAND=${CONFIRMCOMMAND:-$_CONFIRMCOMMAND}

#    if [ "${CONFIRMCOMMAND}" = "Y" ]; then
#        return 0;
#    else
#       return 1;
#    fi
}

confirmCommand;

if [ "$?" = 0 ] && [ "${CONFIRMCOMMAND}" = "Y" ]; then
    exit 0;
fi

exit 1;
