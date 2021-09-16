ARG BASE_IMAGE
FROM $BASE_IMAGE

# Install whatever nodejs version you want
ENV NVM_DIR=/usr/local/nvm
ENV NODE_DEFAULT_VERSION=10
ENV NODE_GLOBAL_PACKAGES="grunt-cli bower"

RUN sudo apt-get remove -y nodejs
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
RUN curl -sL https://raw.githubusercontent.com/nvm-sh/nvm/v0.37.2/install.sh -o install_nvm.sh && \
    mkdir -p $NVM_DIR && bash install_nvm.sh && \
    echo "source $NVM_DIR/nvm.sh" >> /etc/profile && \
    bash -ic "nvm install $NODE_DEFAULT_VERSION && nvm use $NODE_DEFAULT_VERSION && nvm install-latest-npm && npm install --global $NODE_GLOBAL_PACKAGES && ln -s \"/usr/local/nvm/versions/node/$NODE_DEFAULT_VERSION/bin/node\" /usr/bin/node" && \
    chmod -R ugo+w $NVM_DIR
