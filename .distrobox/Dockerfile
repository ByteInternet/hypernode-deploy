FROM debian:10

ENV LANG C.UTF-8
ENV LC_ALL C.UTF-8

# Install general purpose dependencies before we get started
RUN apt update && apt install -y \
  apt-transport-https bash-completion ca-certificates curl git gpg gnupg htop locales lsb-release ripgrep rsync vim vim-nox wget zip

# Install vscode repository
RUN wget -qO- https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > packages.microsoft.gpg && \
  install -o root -g root -m 644 packages.microsoft.gpg /etc/apt/trusted.gpg.d/ && \
  sh -c 'echo "deb [arch=amd64,arm64,armhf signed-by=/etc/apt/trusted.gpg.d/packages.microsoft.gpg] https://packages.microsoft.com/repos/code stable main" > /etc/apt/sources.list.d/vscode.list' && \
  rm -f packages.microsoft.gpg

# Install Ondrej PHP PPA
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
  echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# Install development dependencies
RUN apt update && apt install -y \
  # building and distribution
  build-essential cmake \
  # php stuff
  php8.1-bcmath php8.1-cli php8.1-curl php8.1-gd php8.1-intl php8.1-mbstring php8.1-mysql php8.1-soap php8.1-zip php8.1-xdebug php8.1-xml \
  # network stuff
  dnsutils net-tools \
  # development stuff
  code meld direnv

RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/master/web/installer -O - -q | php -- --quiet && \
  mv composer.phar /usr/local/bin/composer

# Install PhpStorm
RUN wget -q https://download-cdn.jetbrains.com/webide/PhpStorm-2022.3.1.tar.gz -O /tmp/phpstorm.tar.gz && \
  mkdir /opt/phpstorm && \
  tar -xzf /tmp/phpstorm.tar.gz -C /opt/phpstorm --strip-components=1 && \
  printf '#!/usr/bin/env bash\nscreen -d -m bash -c "/opt/phpstorm/bin/phpstorm.sh $@"' > /usr/local/bin/phpstorm && \
  chmod +x /usr/local/bin/phpstorm
