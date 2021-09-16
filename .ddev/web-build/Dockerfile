ARG BASE_IMAGE
FROM $BASE_IMAGE

# Install whatever nodejs version you want
ENV NVM_DIR=/usr/local/nvm

RUN sudo apt-get remove -y nodejs
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
RUN curl -sL https://raw.githubusercontent.com/nvm-sh/nvm/v0.37.2/install.sh -o install_nvm.sh && \
    mkdir -p $NVM_DIR && bash install_nvm.sh && \
    echo "source $NVM_DIR/nvm.sh" >> /etc/profile && \
    bash -ic "nvm install --lts --default && nvm install-latest-npm" && \
    chmod -R ugo+w $NVM_DIR
