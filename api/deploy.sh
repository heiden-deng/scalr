#!/bin/bash

echo 'Generation specifications and documentation for the API'

if [ -z $1 ]; then
    echo 'Version is mandatory'
    exit 1
fi

scopes=( 'user' 'account' )

if [ ! -z $2 ]; then
    if [[ ${scopes[@]} =~ $2 ]]; then
        scopes=( "$2" )
    else
        echo "Invalid scope $2. The correct values: ${scopes[@]}"
        exit 1
    fi
fi

api_directory=$( cd "$(dirname "${BASH_SOURCE}")" ; pwd -P )

[ -d $api_directory/${1} ] || mkdir $api_directory/${1}
    for scope in "${scopes[@]}"; do
        echo "Converting ${scope} scope"
            if python $api_directory/convert.py write $api_directory/${scope}.yaml $api_directory/${scope}-autogenerated.yaml; then
                cp -rf $api_directory/${scope}-autogenerated.yaml $api_directory/${1}/${scope}-autogenerated.yaml
                cp -rf $api_directory/${scope}.yaml $api_directory/${1}/${scope}.yaml
            else
                echo "Error converting"
                exit 1
            fi
    done

    echo 'Generating autodoc'
    rm -rf $api_directory/doc/autodoc
    python $api_directory/autodoc.py ${1}

echo 'Completed'

exit 0