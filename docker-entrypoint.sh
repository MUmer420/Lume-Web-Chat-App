#!/bin/bash
set -e

# Disable all MPM modules to clear conflicts
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2dismod mpm_prefork 2>/dev/null || true

# Explicitly enable only pre-fork for PHP
a2enmod mpm_prefork

# Start Apache in the foreground normally
exec apache2-foreground