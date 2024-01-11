FROM php:8.3-alpine

ARG HELM_VERSION="v3.13.2"
ARG KUBECTL_VERSION="v1.28.2"

# Install Docker and base dependencies
RUN apk add --no-cache ca-certificates openssl openssh-client docker-cli docker-cli-buildx curl bash git

# Install Helm
RUN curl -fsSL -o get_helm.sh https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 && \
    chmod 700 get_helm.sh && \
    DESIRED_VERSION=${HELM_VERSION} ./get_helm.sh && \
    rm ./get_helm.sh

# Install kubectl
RUN ARCH=$(arch | sed s/aarch64/arm64/ | sed s/x86_64/amd64/) && \
    curl -LO "https://dl.k8s.io/release/${KUBECTL_VERSION}/bin/linux/${ARCH}/kubectl" && \
    mv kubectl /usr/bin/kubectl && \
    chmod +x /usr/bin/kubectl

# Copy Symfony Launchpad
COPY sf.phar /root/.sflaunchpad/
COPY sf.phar.pubkey /root/.sflaunchpad/

RUN mkdir -p /usr/local/bin &&  \
    ln -sf /root/.sflaunchpad/sf.phar /usr/local/bin/sf && \
    chmod +x /usr/local/bin/sf

CMD ["bash"]