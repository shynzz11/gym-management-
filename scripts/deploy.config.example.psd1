# Copy to deploy.config.psd1 (gitignored) and fill in the values from hPanel -> Advanced -> SSH Access.
# If the server needs a specific PHP binary, put that in .deploy.env on the server (see deploy.sh), not here.
@{
    SshHost = '123.45.67.89'
    SshPort = 65002
    SshUser = 'u123456789'

    # App directory on the server, relative to your home directory.
    AppPath = 'iron-nest'
}
