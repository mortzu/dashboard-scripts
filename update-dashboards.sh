#! /usr/bin/env bash
# 2016, Moritz Kaspar Rudert (mortzu) <post@moritzrudert.de>.
# All rights reserved.

# Redistribution and use in source and binary forms, with or without modification, are
# permitted provided that the following conditions are met:
#
# * Redistributions of source code must retain the above copyright notice, this list of
#   conditions and the following disclaimer.
#
# * Redistributions in binary form must reproduce the above copyright notice, this list
#   of conditions and the following disclaimer in the documentation and/or other materials
#   provided with the distribution.
#
# * The names of its contributors may not be used to endorse or promote products derived
#   from this software without specific prior written permission.
#
# * Feel free to send Club Mate to support the work.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
# OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
# AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
# AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
# CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
# SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
# THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
# OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.

# Set path to defaults
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

declare -a FIVE_MINUTELY=(bsag.php)
declare -a DAILY=(entsorgung.php)
declare -a HOURLY=(mensa.php)
declare -a MINUTELY=()

# Directory with scripts
DASHBOARDS_DIRECTORY='/opt/dashboard-scripts'

# Change to directory
cd "$DASHBOARDS_DIRECTORY"

# Different run levels
case "$1" in
  five-minutely)
    for ITEM in "${FIVE_MINUTELY[@]}"; do
      "${DASHBOARDS_DIRECTORY}/${ITEM}"
    done
  ;;
  minutely)
    for ITEM in "${MINUTELY[@]}"; do
      "${DASHBOARDS_DIRECTORY}/${ITEM}"
    done
  ;;
  hourly)
    for ITEM in "${HOURLY[@]}"; do
      "${DASHBOARDS_DIRECTORY}/${ITEM}"
    done
  ;;
  daily)
    for ITEM in "${DAILY[@]}"; do
      "${DASHBOARDS_DIRECTORY}/${ITEM}"
    done
  ;;
  *)
    echo "Usage: /etc/init.d/$(basename $0) {five-minutely|minutely|hourly}"
    exit 1
  ;;
esac
