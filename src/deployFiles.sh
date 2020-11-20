#!/bin/dash
#
# Manual process of php-cluster-queue --action=defaults
#
# ./deploy
# src/deployFiles.sh
#
###
# Copies bacic scripts to the nodes
# - then: start install script there
#
# Usage:
#   sh ./src/deployFiles.sh debnode01 debnode02 debnode03 debnode04 # testnodes
#
if [ "$*" = "" ]; then
    echo "Usage: $0 node1 node2 node3 otherhostname"
    exit 1;
else
    NODELIST="$*";
    echo "Using given nodelist: ${NODELIST}"
fi

for NODE in ${NODELIST}; do
    echo "###############";
    echo "# node: ${NODE}";
#     # Examples:
#     ssh root@${NODE} "mkdir -p /root/ha-inst-tmp";
#     scp "src/debNodeInstall.sh" "root@${NODE}:/root/ha-inst-tmp";
#     scp "skel/etc/apt/sources.list" "root@${NODE}:/etc/apt/sources.list"
#     scp skel/etc/apt/sources.list.d/* "root@${NODE}:/etc/apt/sources.list.d"
#
#     # .shell_aliases  .zsh_history  .zshrc  .zshrc.local  .zshrc.pre
#     scp "skel/homes/.shell_aliases" "root@${NODE}:/root/"
#     scp "skel/homes/.zshrc" "root@${NODE}:/root/"
#     scp "skel/homes/.zshrc.local" "root@${NODE}:/root/"
#     scp "skel/homes/.zshrc.pre" "root@${NODE}:/root/"
#     scp "skel/homes/.gitconfig" "root@${NODE}:/root/"
#     scp "skel/homes/.vimrc" "root@${NODE}:/root/"
#     scp "skel/homes/.mysql_history" "root@${NODE}:/root/.mysql_history_init"
#
#     scp "skel/homes/.shell_aliases" "linux@${NODE}:~/"
#     scp "skel/homes/.zshrc" "linux@${NODE}:~/"
#     scp "skel/homes/.zshrc.local" "linux@${NODE}:~/"
#     scp "skel/homes/.zshrc.pre" "linux@${NODE}:~/"
#     scp "skel/homes/.gitconfig" "linux@${NODE}:~/"
#     scp "skel/homes/.vimrc" "linux@${NODE}:"
#     scp "skel/homes/.ssh/authorized_keys_debnode" "linux@${NODE}:/root/.ssh/authorized_keys"
#     scp "skel/homes/.ssh/authorized_keys_debnode" "root@${NODE}:/root/.ssh/authorized_keys"
#     scp "root@${NODE}:/root/.ssh/id_rsa.pub" ~/.ssh/id_rsa_${NODE}.pub
#     scp "root@${NODE}:/root/.ssh/id_rsa" ~/.ssh/id_rsa_${NODE}

done;


