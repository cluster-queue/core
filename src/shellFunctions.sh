#!/bin/sh

confirmCommand() {
    _CONFIRMCOMMAND='n';
    read -p "Confirm (Y)es,(n)o (def:'$_CONFIRMCOMMAND'): " CONFIRMCOMMAND
    CONFIRMCOMMAND=${CONFIRMCOMMAND:-$_CONFIRMCOMMAND}

    return 0;
}

# /* username = $1 */
changePassword() {

    unset -v CUSTOMPASSWORD
    unset -v CUSTOMPASSWORD
    unset -v CUSTOMPASSWORD2

    _CUSTOMPASSWORD='';
    CUSTOMPASSWORD='';
    CUSTOMPASSWORD2='';

    if [ "${CUSTOMUSERNAME}" = "" ] && [ "$1" != "" ]; then
        CUSTOMUSERNAME=$1;
        echo "User: '$1'";
    fi

    read -p "Enter new password: " CUSTOMPASSWORD
    CUSTOMPASSWORD=${CUSTOMPASSWORD:-$_CUSTOMPASSWORD}

    read -p "Confirm new password: " CUSTOMPASSWORD2
    CUSTOMPASSWORD2=${CUSTOMPASSWORD2:-$_CUSTOMPASSWORD}

    if [ "${CUSTOMPASSWORD}" = "${CUSTOMPASSWORD2}" ] && [ "${CUSTOMPASSWORD}" != "" ] && [ "${CUSTOMPASSWORD2}" != "" ]; then

        echo "${CUSTOMUSERNAME}:${CUSTOMPASSWORD}" | chpasswd

        echo "'${CUSTOMUSERNAME}' password changed!";

        return 0;
    else
        echo "Err. Password NOT changed!";

        return 1;
    fi
}

# contains(heystackstring, stringpart)
# Returns 0 if stringpart found in heystackstring otherwise returns 1
contains() {
    string="$1"
    substring="$2"
    if test "${string#*$substring}" != "$string"
    then
        return 0 # $stringpart IN string
    else
        return 1 # $stringpart NOT in string
    fi
}

