#!/bin/bash
###############################################################################
#
# install.sh - Installs/Updates NagVis
#
# Copyright (c) 2004-2009 NagVis Project (Contact: lars@vertical-visions.de)
#
# Development:
#  Wolfgang Nieder
#  Lars Michelsen <lars@vertical-visions.de>
#
# License:
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License version 2 as
# published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
#
###############################################################################

# Some initializations
###############################################################################

# Installer version
INSTALLER_VERSION="0.2.8"
# Default action
INSTALLER_ACTION="install"
# Be quiet? (Enable/Disable confirmations)
INSTALLER_QUIET=0
# Should the installer change config options when possible?
INSTALLER_CONFIG_MOD="n"
# files to ignore/delete
IGNORE_DEMO=""
# backends to use
NAGVIS_BACKENDS="mklivestatus,ndo2db,ido2db,ndo2fs,merlinmy"
# Return Code
RC=0
# data source
SOURCE=nagios
# skip checks
FORCE=0
REMOVE="n"
LOG=install.log
CALL="$0"

# Default Path to Graphviz binaries
GRAPHVIZ_PATH="/usr/local/bin"
# Version of NagVis to be installed
NAGVIS_VER=""
[ -f share/server/core/defines/global.php ]&&NAGVIS_VER=`cat share/server/core/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
[ -f nagvis/includes/defines/global.php ]&&NAGVIS_VER=`cat nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`

# Version of old NagVis (will be detected if update)
NAGVIS_VER_OLD=""
# Relative path to the NagVis configuration file
NAGVIS_CONF="etc/nagvis.ini.php"
# Relative path to the NagVis SQLite auth database
NAGVIS_AUTH_DB="etc/auth.db"
# Default nagios web conf
HTML_SAMPLE="etc/apache2-nagvis.conf-sample"
# Default nagios web conf
HTML_CONF="nagvis.conf"
# Saving current timestamp for backup when updating
DATE=`date +%s`
# Web path to the NagVis base directory
HTML_PATH=""
# Path to webserver conf
WEB_PATH=""
# Default webserver user
WEB_USER=""
# Default webserver group
WEB_GROUP=""

# Version prerequisites
[ -f share/server/core/defines/global.php ]&&NEED_PHP_VERSION=`cat share/server/core/defines/global.php | grep CONST_NEEDED_PHP_VERSION | awk -F"'" '{ print $4 }'`
[ -f nagvis/includes/defines/global.php ]&&NEED_PHP_VERSION=`cat nagvis/includes/defines/global.php | grep CONST_NEEDED_PHP_VERSION | awk -F"'" '{ print $4 }'`
[ -z "$NEED_PHP_VERSION" ] && NEED_PHP_VERSION="5.0"

NEED_PHP_MODULES="gd mysql mbstring gettext session xml"
NEED_GV_MOD="dot neato twopi circo fdp"
NEED_GV_VERSION=2.14

LINE_SIZE=78
GREP_INCOMPLETE=0

# Function definitions
###############################################################################

# format version string
fmt_version() {
   LNG=${2:-8}
	echo `perl -e '$v = $ARGV[0]; $v =~ s/a/.0.0/i; $v =~ s/b/.0.2/i; $v =~ s/rc/.0.4/i; @f = split (/\./,$v); for (0..$#f) { $z .= sprintf "%02d", $f[$_]; }; print substr($z."0"x$ARGV[1],0,$ARGV[1]);' $1 $LNG` 
}

# Print usage
usage() {
cat <<EOD
NagVis Installer $INSTALLER_VERSION
Installs or updates NagVis on your system.

Usage: $0 [OPTIONS]

Parameters:
  -n <PATH>     Path to Nagios/Icinga directory. The default value is $NAGIOS_PATH
  -B <BINARY>   Full path to the Nagios/Icinga binary. The default value is $NAGIOS_PATH/bin/nagios
  -m <BINARY>   Full path to the NDO/IDO module. The default value is $NAGIOS_PATH/bin/ndo2db
  -b <PATH>     Path to graphviz binaries. The default value is $GRAPHVIZ_PATH
  -p <PATH>     Path to NagVis base directory. The default value is $NAGIOS_PATH/share/nagvis
  -u <USER>     User who runs the webserver
  -g <GROUP>    Group who runs the webserver
  -w <PATH>     Path to the webserver config files
  -W <PATH      Web path to the NagVis base directory (Default: $HTML_PATH) 
  -i <BACKENDs> comma separated list of backend interfaces to use:
                  Available backends: mklivestatus, ndo2db, ido2db, ndo2fs, merlinmy
  -s <SOURCE>   Data source, defaults to Nagios, may be Icinga
  -o            omit demo files
  -r            remove backup directory after successful installation
  -q            Quiet mode. The installer won't ask for confirmation of what to do
                This can be useful for automatic or scripted deployment
                WARNING: Only use this if you know what you are doing
  -v            Version information
  -h            This message

EOD
#  -c [y|n]      Update configuration files when possible?
}

# Print version information
version() {
cat <<EOD
NagVis installer, version $INSTALLER_VERSION
Copyright (C) 2004-2009 NagVis Project

License: GNU General Public License version 2

Development:
- Wolfgang Nieder
- Lars Michelsen <lars@vertical-visions.de>

EOD
}

# Print line
line() {
	DASHES="--------------------------------------------------------------------------------------------------------------"
	SIZE2=`expr $LINE_SIZE - 4`
	if [ -z "$1" ]; then
		OUT=`printf "+%${LINE_SIZE}.${LINE_SIZE}s+\n" $DASHES`
	else
		if [ -z "$2" ]; then
			OUT=`printf "+--- %s\n" "$1"`
		else
			OUT=`printf "+--- %${SIZE2}.${SIZE2}s+\n" "$1 $DASHES"`
		fi
	fi
	echo "$OUT"
	echo "$OUT" >> $LOG
}
# Print text
text() {
	SIZE2=`expr $LINE_SIZE - 3`
	if [ -z "$1" ]; then
		OUT=`printf "%s%${LINE_SIZE}s%s\n" "|" "" "|"`
	else
		if [ -z "$2" ]; then
			OUT=`printf "%s\n" "$1"`
		else
			OUT=`printf "%-${LINE_SIZE}.${LINE_SIZE}s %s\n" "$1" "$2"`
		fi
	fi
	echo "$OUT"
	echo "$OUT" >> $LOG
}

# Ask user for confirmation
confirm() {
	echo -n "| $1 [$2]: "
	read ANS
	[ -z $ANS ] && ANS=$2
	ANS=`echo $ANS | tr "jyos" "YYYY" | cut -c 1,1`
 	[ "$ANS" != "Y" ]&&ANS="N"	
}

# Print welcome message
welcome() {
cat <<EOD
+------------------------------------------------------------------------------+
| Welcome to NagVis Installer $INSTALLER_VERSION                                            |
+------------------------------------------------------------------------------+
| This script is built to facilitate the NagVis installation and update        |
| procedure for you. The installer has been tested on the following systems:   |
| - Debian Etch (4.0)                                                          |
| - Ubuntu Hardy (8.04)                                                        |
| - Ubuntu Intrepid (8.10)                                                     |
| - SuSE Linux Enterprise Server 10                                            |
|                                                                              |
| Similar distributions to the ones mentioned above should work as well.       |
| That (hopefully) includes RedHat, Fedora, CentOS, OpenSuSE                   |
|                                                                              |
| If you experience any problems using these or other distributions, please    |
| report that to the NagVis team.                                              |
+------------------------------------------------------------------------------+
EOD
if [ $INSTALLER_QUIET -ne 1 ]; then
	confirm "Do you want to proceed?" "y"
	if [ "$ANS" != "Y" ]; then
		text
		text "| Installer aborted, exiting..." "|"
		line ""
		exit 1
	fi
fi
}

# Print module state, exit if necessary
log() {
	SIZE=`expr $LINE_SIZE - 8` 
	if [ -z "$2" ]; then
		OUT=`printf "%-${SIZE}s %s\n" "| $1" "MISSING |"`
		RC=1
	elif [ "$2" = "needed" ]; then
		OUT="$1 needed"
		RC=1
	elif [ "$2" = "warning" ]; then
		OUT=`printf "%-${LINE_SIZE}s |\n" "| $1"`
	elif [ "$2" = "done" ]; then
		OUT=`printf "%-${SIZE}s %s\n" "| $1" "  done  |"`
	else	
		OUT=`printf "%-${SIZE}s %s\n" "| $1" "  found |"`
	fi
	echo "$OUT"
	echo "$OUT" >> $LOG
}
 
# Check Backend module prerequisites
check_backend() {
	BACKENDS=""
	text "| Checking Backends: $NAGVIS_BACKEND" "|"
	# Check NDO module if necessary
	if [ $INSTALLER_QUIET -ne 1 ]; then
		if [ -z "$NAGVIS_BACKEND" ]; then
			ASK=`echo $NAGVIS_BACKENDS | sed 's/,/ /g'` 
			for i in $ASK; do
				confirm "Do you want to use backend $i?" "n"
				if [ "$ANS" = "Y" ]; then
					BACKENDS=$BACKENDS,$i
				fi
			done
			NAGVIS_BACKEND=$BACKENDS
		fi
		NAGVIS_BACKEND=${NAGVIS_BACKEND#,}
	fi
	
	echo $NAGVIS_BACKEND | grep -i "MKLIVESTATUS" >/dev/null
	if [ $? -eq 0 ]; then
		# FIXME: Add mklivestatus checks
		# - Check if php socket module is availsable
		# - Check if livestatus.o is avialable
		text "|   * Sorry, no checks yet for mklivestatus backend" "|"
		
		if [ "$BACKENDS" = "" ]; then
			BACKENDS="mklivestatus"
		else
			BACKENDS="${BACKENDS},mklivestatus"
		fi
	fi
	
	echo $NAGVIS_BACKEND | grep -i "NDO2DB" >/dev/null
	if [ $? -eq 0 ]; then
		# Check NDO
		[ -z "$NDO_MOD" ]&&NDO_MOD="$NAGIOS_PATH/bin/ndo2db-${NAGVER}x"
		NDO=`$NDO_MOD --version 2>/dev/null | grep -i "^NDO2DB"`

		# maybe somebody removed version information
		if [ -z "$NDO" ]; then
			NDO_MOD="$NAGIOS_PATH/bin/ndo2db"
			NDO=`$NDO_MOD --version 2>/dev/null | grep -i "^NDO2DB"`
		fi
		[ -z "$NDO" ]&&NDO_MOD="NDO Module ndo2db"
		log "  $NDO_MOD (ndo2db)" $NDO
		
		if [ "$BACKENDS" = "" ]; then
			BACKENDS="ndo2db"
		else
			BACKENDS="${BACKENDS},ndo2db"
		fi
	fi

	echo $NAGVIS_BACKEND | grep -i "IDO2DB" >/dev/null
	if [ $? -eq 0 ]; then
		# Check IDO
		if [ -z "$NDO" ]; then
			NDO_MOD="$NAGIOS_PATH/bin/ido2db"
		fi
		NDO=`$NDO_MOD --version 2>/dev/null | grep -i "^IDO2DB"`
		[ -z "$NDO" ]&&NDO_MOD="IDO Module ido2db"
		log "  $NDO_MOD (ido2db)" $NDO
		
		if [ "$BACKENDS" = "" ]; then
			BACKENDS="ido2db"
		else
			BACKENDS="${BACKENDS},ido2db"
		fi
	fi

	# Check NDO2FS prerequisites if necessary
	echo $NAGVIS_BACKEND | grep -i "NDO2FS" >/dev/null
	if [ $? -eq 0 ]; then
		JSON=`perl -e '$erg=eval "use JSON::XS;1"; print "found" if ($erg==1)'`
		log "  Checking perl module JSON::XS (ndo2fs)" $JSON
		
		if [ "$BACKENDS" = "" ]; then
			BACKENDS="ndo2fs"
		else
			BACKENDS="${BACKENDS},ndo2fs"
		fi
	fi

	# Check merlin prerequisites if necessary
	echo $NAGVIS_BACKEND | grep -i "MERLINMY" >/dev/null
	if [ $? -eq 0 ]; then
		text "|   *** Sorry, no checks yet for merlin" "|"
		
		if [ "$BACKENDS" = "" ]; then
			BACKENDS="merlinmy"
		else
			BACKENDS="${BACKENDS},merlinmy"
		fi
	fi
	
	
	if [ -z "$BACKENDS" ]; then
		log "NO (valid) backend(s) specified"
	fi
	
	BACKENDS=${BACKENDS#,}
}

# Check Apache PHP module
check_apache_php() {
	DIR=$1
	[ ! -d $DIR ] && return
	WEB_PATH=${DIR%%/}
	[ -d $DIR/conf.d ]&&WEB_PATH=$WEB_PATH/conf.d
	
	# The apache user/group are defined by env vars in Ubuntu, set them here
	[ -f $DIR/envvars ] && source $DIR/envvars
	
	MODPHP=`find $DIR -type f -exec grep -ie "mod_php.*\.so" -e "libphp.*\.so" {} \; | tr -s " " | cut -d" " -f3 | uniq`
	
	# Only try to detect user when not set or empty
	if [ -z "$WEB_USER" ]; then
		WEB_USER=`find $DIR -type f -name "*conf" -exec grep -i "^User" {} \; | grep -vi "UserDir" | cut -d" " -f2 | uniq`
		VAR=`echo $WEB_USER | grep "$" >/dev/null 2>&1`
		[ $? -eq 0 ] && WEB_USER=`eval "echo $WEB_USER"`
	fi

	# Only try to detect group when not set or empty
	if [ -z "$WEB_GROUP" ]; then
		WEB_GROUP=`find $DIR -type f -name "*.conf" -exec grep -i "^Group" {} \; | cut -d" " -f2 | uniq`
		VAR=`echo $WEB_GROUP | grep "$" >/dev/null 2>&1`
		[ $? -eq 0 ] && WEB_GROUP=`eval "echo $WEB_GROUP"`
	fi
}

# Check Graphviz version by installed system package
check_graphviz_version() {
	if [ "${PKG##/*/}" = "dpkg" ]; then
		GRAPHVIZ_VER=`$PKG -l "graphviz" | grep "graphviz" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	elif [ "${PKG##/*/}" = "rpm" ]; then
		GRAPHVIZ_VER=`$PKG -qa "graphviz" | sed "s/graphviz-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
	else
		GRAPHVIZ_VER=`$PKG list installed "graphviz" | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	fi

   GRAPHVIZ_FMT=`fmt_version $GRAPHVIZ_VER` 
	if [ -z "$GRAPHVIZ_VER" ]; then
		log "WARNING: The Graphviz package was not found." "warning"
		log "         This may not be a problem if you installed it from source" "warning"
	else 
		log "Graphviz $GRAPHVIZ_VER" $GRAPHVIZ_VER
		if [ $GRAPHVIZ_FMT -lt $GRAPHVIZ_REQ ]; then
			log "|  Error: Version >= $1" "needed"
		fi
	fi
}

# Check Graphviz Modules
check_graphviz_modules() {
	for MOD in $1
	do
		TMP=`which $MOD 2>/dev/null`
		[ -z "$TMP" ] && TMP=`which $GRAPHVIZ_PATH/$MOD 2>/dev/null`
		if [ -s "$TMP" ]; then
			GV_MOD_VER=`$MOD -V 2>&1`
			GV_MOD_VER=${GV_MOD_VER#*version }
			GV_MOD_VER=${GV_MOD_VER% (*}
		fi
		
		log "  Graphviz Module $MOD $GV_MOD_VER" $TMP
		if [ -n "$GV_MOD_VER" ]; then
			GV_MOD_FMT=`fmt_version $GV_MOD_VER` 
		
			if [ $GV_MOD_FMT -lt $GRAPHVIZ_REQ ]; then
				log "|  Error: Version >= $2" "needed"
			fi
		fi
	done
}

# Check PHP Version
check_php_version() {
	if [ "${PKG##/*/}" = "dpkg" ]; then
		PHP_VER=`$PKG -l "php[0-9]" 2>/dev/null | grep "php" | grep ii | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	elif [ "${PKG##/*/}" = "rpm" ]; then
		PHP_VER=`$PKG -qa "php[0-9]" | sed "s/php[0-9]\-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
	else
		PHP_VER=`$PKG list installed "php[0-9]" 2>/dev/null | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
	fi
	PHP=`which php 2>/dev/null`
	if [ -z "$PHP_VER" ]; then
		if [ -s "$PHP" -a -x "$PHP" ]; then
			PHP_VER=`$PHP -v | head -1 | sed -e "s/PHP \([0-9\]\+\.[0-9\]\+\).*/\1/"`
		fi
	fi
	log "PHP $PHP_VER" $PHP_VER
	if [ -n "$PHP_VER" ]; then
		PHP_FMT=`fmt_version $PHP_VER` 
		PHP_REQ=`fmt_version $NEED_PHP_VERSION` 
	
		if [ $PHP_FMT -lt $PHP_REQ ]; then
			log "|  Error: Version >= $1" "needed"
		fi
	fi
}

# Check PHP modules
check_php_modules() {
	for MOD in $1
	do
		if [ "${PKG##/*/}" = "dpkg" ]; then
			MOD_VER=`$PKG -l "php[0-9]-$MOD" 2>/dev/null | grep "php" | grep "ii" | awk -F' ' '{ print $3 }' | sed "s/-.*$//" | cut -d"." -f1,2`
		elif [ "${PKG##/*/}" = "rpm" ]; then
			MOD_VER=`$PKG -qa "php[0-9]?-$MOD" | sed "s/php[0-9]?\-$MOD-//g" | sed "s/-.*$//" | cut -d"." -f1,2`
		else
			MOD_VER=`$PKG list installed "php[0-9]-$MOD" 2>/dev/null | grep "php" | grep "installed" | awk -F' ' '{ print $2 }' | sed "s/-.*$//" | cut -d"." -f1,2`
		fi

		# maybe compiled in module
		if [ -s "$PHP" -a -x "$PHP" ]; then
			TMP=`$PHP -m 2>&1 | grep -i "^$MOD$"`
			[ -z "$MOD_VER" -a -n "$TMP" ]&&MOD_VER="compiled_in"
		fi
		
		if [ -z "$TMP" ]; then
			TMP=`find /etc/ -type f -name "php?" -exec grep -ie "$MOD" {} \; | cut -d"=" -f2`
		fi
		
		# RedHat: /etc/php.d/...
		if [ -z "$TMP" -a -d /etc/php.d/ ]; then
			TMP=`grep -ie "$MOD" /etc/php.d/* | cut -d"=" -f2`
		fi
		
		# Ubuntu: /etc/php5/conf.d
		if [ -z "$TMP" -a -d /etc/php5/conf.d ]; then
			TMP=`grep -ie "extension=$MOD" /etc/php5/conf.d/* | sed 's/.*=//;s/\.so*//'`
		fi
		
		log "  Module: $MOD $MOD_VER" $TMP

		if [ -n "$MOD_VER" ]; then
			if [ `echo "$2 $MOD_VER" | awk '{if ($1 > $2) print $1; else print $2}'` = $2 ]; then
				log "  WARNING: Module $MOD not found." "warning"
				log "           This may not be a problem. You can ignore this if your php" "warning"
				log "           was compiled with the module included" "warning"
			fi
		fi
	done
}

# Check return code
chk_rc() {
	RC=$?
	if [ $RC -ne 0 ]; then
		echo $* Return Code: $RC
		exit 1
	else
		if [ "$2" != "" ]; then
			echo "$2"
		fi
	fi
}

#copy "default*" "$USERFILES_DIR/templates/pages" "pages templates"
copy() {
	DONE=""
	
	# DEBUG: [ -n "$LINE" ] && line "$LINE"
	[ -n "$LINE" ] && DONE=`log "$LINE" done` 
	
	# Copy single file
	if [ -f "$NAGVIS_PATH_OLD/$2" ]; then
		cp -p $NAGVIS_PATH_OLD/$2 $NAGVIS_PATH/$2
		chk_rc "|  Error copying file $3" "$DONE"
	fi
	
	# Copy old directory contents to new directory
	if [ -d "$NAGVIS_PATH_OLD/$2" -a ! -d "$3" ]; then
		# Get files to copy
		FILES=`find $NAGVIS_PATH_OLD/$2 -type f`

		# Maybe exclude some files
		if [ "$1" != "" ]; then
			FILES=`echo "$FILES" | grep -vE $1`
		fi
		
		if [ "$FILES" != "" ]; then
			cp -pr `echo "$FILES" | xargs` $NAGVIS_PATH/$2
			chk_rc "|  Error copying $3" "$DONE"
		fi
	fi
	
	# Copy directory to directory
	if [ -d "$3" ]; then
		cp -pr $2 $3
		chk_rc "|  Error copying $2 to $3" "$DONE"
	fi
	
	LINE=""
	DONE=""
}

set_perm() {
	if [ -d "$2" -o -f "$2" ]; then
		DONE=`log "$2" done` 
		chmod $1 $2
		chk_rc "| Error setting permissions for $2" "$DONE"
	fi
}

makedir() {
	if [ ! -d $1 ]; then
		DONE=`log "Creating directory $1..." done` 
		mkdir -p $1
		chk_rc "|  Error creating directory $1" "$DONE"
	fi
}

cmp() {
	cat $1 | sed 's#\(var\)\s*\(\S*\)\s*=\s*#\1 \2=#;s#^\s*##;s#\s*$##;s#\t+# #g' | awk '
	BEGIN { OK=1; braces=0 }
	{
		# Remove /* */ one line comments
		sub(/\/\*[^@]*\*\//,"");
		# Remove // comments (line beginning)
		sub(/^\/\/.*/,"");
		
		# Count braces
		anz1 = gsub(/\{/,"{");
		anz2 = gsub(/}/,"}");
		
		if (OK == 1) {
			braces += anz1;
			braces -= anz2;
		}
	}
	/\/\*/ {
		c = gsub(/\/\*[^@]*$/,"");
		if(c > 0) {
			OK=0;
		}
	}
	/\*\/$/ {
		c = gsub(/^[^@]*\*\//,"");
		if(c > 0) {
			OK=1;
		}
	}
	{
		line = $0;
		#anz = gsub(/function/," function");
		#ch = substr(line,length(line));
		if (OK == 1) {
			if (length(line) > 0) {
				#if (ch == "}") {
				#	if (braces == 0) {
				#		if (length(line) > 0) {
				#			print line
				#		}
				#		line = ""
				#	}
				#}
				#line = line $0;
				
				print line;
			}
		}
	}
	' >> $OUT
}

cmp_js() {
	cd $NAGVIS_PATH/share/frontend/nagvis-js/js/
	OUT=NagVisCompressed.js
	>$OUT
	cmp nagvis.js
	cmp popupWindow.js
	cmp ExtBase.js
	cmp frontendMessage.js
	cmp frontendEventlog.js
	cmp hover.js
	cmp frontendContext.js
	cmp ajax.js
	cmp dynfavicon.js
	cmp frontend.js
	cmp lines.js
	cmp overlib.js
	cmp NagVisObject.js
	cmp NagVisStatefulObject.js
	cmp NagVisStatelessObject.js
	cmp NagVisHost.js
	cmp NagVisService.js
	cmp NagVisHostgroup.js
	cmp NagVisServicegroup.js
	cmp NagVisMap.js
	cmp NagVisShape.js
	cmp NagVisLine.js
	cmp NagVisTextbox.js
	cmp NagVisRotation.js
	cmp wz_jsgraphics.js
}

# Main program starting
###############################################################################

# More (re)initialisations

echo "`date`: Installer $INSTALLER_VERSION" > $LOG
# Version info
NAGVIS_TAG=`fmt_version "$NAGVIS_VER"`
[ -z "$NAGVIS_TAG" ]&&NAGVIS_TAG=01000000
# Default Nagios path
NAGIOS_PATH="/usr/local/$SOURCE"
# Default Path to NagVis base
NAGVIS_PATH="/usr/local/nagvis"
[ $NAGVIS_TAG -lt 01050000 ]&&NAGVIS_PATH="$NAGIOS_PATH/share/nagvis"
# Default nagios share webserver path
HTML_PATH="/nagvis"
[ $NAGVIS_TAG -lt 01050000 ]&&HTML_PATH="/$SOURCE/nagvis"

# Process command line options
if [ $# -gt 0 ]; then
	while getopts "n:B:m:p:w:W:u:b:g:c:i:s:ohqvFr" options; do
		case $options in
			n)
				NAGIOS_PATH=$OPTARG

				# NagVis below 1.5 depends on the given Nagios path
				# So set it here when set by param
				[ $NAGVIS_TAG -lt 01050000 ]&&NAGVIS_PATH="${NAGIOS_PATH%/}/share/nagvis"
			;;
			B)
				NAGIOS_BIN=$OPTARG
			;;
			m)
				NDO_MOD=$OPTARG
			;;
			b)
				GRAPHVIZ_PATH=$OPTARG
			;;
			p)
				NAGVIS_PATH=$OPTARG
			;;
			w)
				WEB_PATH=$OPTARG
			;;
			W)
				HTML_PATH=$OPTARG
			;;
			u)
				WEB_USER=$OPTARG
			;;
			g)
				WEB_GROUP=$OPTARG
			;;
			i)
				NAGVIS_BACKEND=$OPTARG
			;;
			o)
				IGNORE_DEMO="demo*cfg demo*png"
			;;
			q)
				INSTALLER_QUIET=1
			;;
			c)
				INSTALLER_CONFIG_MOD=$OPTARG
			;;
			s)
				SOURCE=`echo $OPTARG | tr [A-Z] [a-z]` 
			;;
			F)
				FORCE=1
			;;
			r)
				REMOVE="y"
			;;
			h)
				usage
				exit 0
			;;
			v)
				version
				exit 0
			;;
			*)
				echo "Error: Unknown option."
				usage
				exit 1
			;;
		esac
	done
fi

if [ $FORCE -eq 1 ]; then
	if [ -z "$WEB_USER" -o -z "$WEB:GROUP" -o -z "$WEB_PATH" ]; then
		echo "ERROR: Using '-F' you also have to specify '-u ...', '-g ...' and '-w ...'"
		exit 1
	fi
fi

# Print welcome message
welcome

# Start gathering information
line ""
text "| Starting installation of NagVis $NAGVIS_VER" "|"
line ""
[ -f /etc/issue ]&&OS=`grep -v "^\s*$" /etc/issue | sed 's/\\\.*//' | head -1` 
[ -n "$OS" ]&&text "| OS  : $OS" "|"
PERL=`perl -e 'print $];'` 
[ -n "$PERL" ]&&text "| Perl: $PERL" "|"
text
line "Checking for tools" "+"
WHICH=`whereis which | awk '{print $2}'` 
if [ -z $WHICH ]; then
	log "'which' not found (maybe package missing). Aborting..."
	exit 1
fi
PKG=`which rpm 2>/dev/null`
[ -u $PKG ] && PKG=`which dpkg 2>/dev/null`
[ -u $PKG ] && PKG=`which yum 2>/dev/null`
if [ -u $PKG ]; then
	log "No packet manager (rpm/dpkg/yum) found. Aborting..."
	exit 1
fi
log "Using packet manager $PKG" $PKG
SED=`which sed` 

# checking grep option as non-Linux might not support "-r"
grep -r INSTALLER_VERSION install.sh >/dev/null 2>&1
if [ $? -ne 0 ]; then
	GREP_INCOMPLETE=1
	log "grep doesn't support option -r" "warning"
fi

text
line "Checking paths" "+"

if [ $FORCE -eq 0 ]; then
	# Get Nagios path
	if [ $INSTALLER_QUIET -ne 1 ]; then
	  echo -n "| Please enter the path to the $SOURCE base directory [$NAGIOS_PATH]: "
	  read APATH
	  if [ ! -z $APATH ]; then
	    NAGIOS_PATH=$APATH
	  fi
	fi

	# Check Nagios path
	if [ -d $NAGIOS_PATH ]; then
		log "$SOURCE path $NAGIOS_PATH" "found"
	else
		echo "| $SOURCE home $NAGIOS_PATH is missing. Aborting..."
		exit 1
	fi
	CALL="$CALL -n $NAGIOS_PATH"

	# NagVis below 1.5 depends on the given Nagios path
	# So set it here when some given by param
	[ $NAGVIS_TAG -lt 01050000 ]&&NAGVIS_PATH="${NAGIOS_PATH%/}/share/nagvis"

	# Get NagVis path
	if [ $INSTALLER_QUIET -ne 1 ]; then
		echo -n "| Please enter the path to NagVis base [$NAGVIS_PATH]: "
		read ABASE
		if [ ! -z $ABASE ]; then
		NAGVIS_PATH=$ABASE
		fi
	fi
	CALL="$CALL -p $NAGVIS_PATH"
fi

text
line "Checking prerequisites" "+"

# Check Nagios version
[ -z "$NAGIOS_BIN" ]&&NAGIOS_BIN="$NAGIOS_PATH/bin/$SOURCE"

if [ -f $NAGIOS_BIN ]; then
	NAGIOS=`$NAGIOS_BIN --version | grep -i "^$SOURCE " | head -1 2>&1`
	log "$NAGIOS" $NAGIOS
else
	log "$SOURCE binary $NAGIOS_BIN"
fi
CALL="$CALL -B $NAGIOS_BIN"

NAGVER=`echo $NAGIOS | sed 's/^.* //' | cut -c1,1`
[ "$SOURCE" = "icinga" ]&&NAGVER=3

if [ $FORCE -eq 0 ]; then
	# Check Backend prerequisites
	check_backend
	CALL="$CALL -b $NAGVIS_BACKEND"

	# Check PHP Version
	check_php_version $NEED_PHP_VERSION

	# Check PHP Modules
	check_php_modules "$NEED_PHP_MODULES" "$NEED_PHP_VERSION"

	# Check Apache PHP Module
	check_apache_php "/etc/apache2/"
	check_apache_php "/etc/apache/"
	check_apache_php "/etc/http/"
	check_apache_php "/etc/httpd/"
	check_apache_php "/usr/local/etc/apache2/"	# FreeBSD
	log "  Apache mod_php" $MODPHP

	# Check Graphviz
	GRAPHVIZ_REQ=`fmt_version $NEED_GV_VERSION` 
	check_graphviz_version $NEED_GV_VERSION

	# Check Graphviz Modules
	check_graphviz_modules "$NEED_GV_MOD" $NEED_GV_VERSION

	if [ $RC -ne 0 ]; then
		text
		line "Errors found during check of prerequisites. Aborting..."
		exit 1
	fi

	text
	line "Trying to detect Apache settings" "+"

	HTML_PATH=${HTML_PATH%/}

	if [ $INSTALLER_QUIET -ne 1 ]; then
		echo -n "| Please enter the web path to NagVis [$HTML_PATH]: "
		read APATH
		if [ ! -z $APATH ]; then
			HTML_PATH=$APATH
		fi
	fi

	if [ $INSTALLER_QUIET -ne 1 ]; then
		echo -n "| Please enter the name of the web-server user [$WEB_USER]: "
		read AUSR
		if [ ! -z $AUSR ]; then
			WEB_USER=$AUSR
		fi
	fi

	if [ $INSTALLER_QUIET -ne 1 ]; then
		echo -n "| Please enter the name of the web-server group [$WEB_GROUP]: "
		read AGRP
		if [ ! -z $AGRP ]; then
			WEB_GROUP=$AGRP
		fi
	fi

fi
if [ ! `getent passwd | cut -d':' -f1 | grep "^$WEB_USER"` = "$WEB_USER" ]; then
	echo "|  Error: User $WEB_USER not found."
	exit 1
fi

if [ ! `getent group | cut -d':' -f1 | grep "^$WEB_GROUP"` = "$WEB_GROUP" ]; then
	echo "|  Error: Group $WEB_GROUP not found."
	exit 1
fi
text "| HTML base directory $HTML_PATH" "|"
CALL="$CALL -u $WEB_USER -g $WEB_GROUP -w $WEB_PATH"

text
line "Checking for existing NagVis" "+"

if [ -d $NAGVIS_PATH ]; then
	INSTALLER_ACTION="update"
	
	if [ -e $NAGVIS_PATH/nagvis/includes/defines/global.php ]; then
		NAGVIS_VER_OLD=`cat $NAGVIS_PATH/nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
	elif [ -e $NAGVIS_PATH/share/nagvis/includes/defines/global.php ]; then
		NAGVIS_VER_OLD=`cat $NAGVIS_PATH/share/nagvis/includes/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
	elif [ -e $NAGVIS_PATH/share/server/core/defines/global.php ]; then
		NAGVIS_VER_OLD=`cat $NAGVIS_PATH/share/server/core/defines/global.php | grep CONST_VERSION | awk -F"'" '{ print $4 }'`
	else
		NAGVIS_VER_OLD="UNKNOWN"
	fi
	
	# Generate the version tag for old version
	if [ "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
		NAGVIS_TAG_OLD=`fmt_version "$NAGVIS_VER_OLD"`
	else
		NAGVIS_TAG_OLD=01000000
	fi
	
	NAGVIS_PATH_OLD=$NAGVIS_PATH.old-$DATE

	log "NagVis $NAGVIS_VER_OLD" $NAGVIS_VER_OLD
fi

if [ "$INSTALLER_ACTION" = "update" ]; then
	if [ $INSTALLER_QUIET -ne 1 ]; then
		confirm "Do you want the installer to update your config files when possible?" "$INSTALLER_CONFIG_MOD"
		INSTALLER_CONFIG_MOD=$ANS
		confirm "Should the backup directory be removed after successful installation?" "$REMOVE"
		REMOVE=$AMOD
	fi
fi

text
line ""
text "| Summary" "|"
line ""
text "| NagVis home will be:           $NAGVIS_PATH" "|"
text "| Owner of NagVis files will be: $WEB_USER" "|"
text "| Group of NagVis files will be: $WEB_GROUP" "|"
text "| Path to Apache config dir is:  $WEB_PATH" "|"
text
if [ "$IGNORE_DEMO" != "" ]; then
	text "| demo files will NOT be copied" "|"
	text
fi
text "| Installation mode:             $INSTALLER_ACTION" "|"
if [ "$INSTALLER_ACTION" = "update" ]; then
	text "| Old version:                   $NAGVIS_VER_OLD" "|"
	text "| New version:                   $NAGVIS_VER" "|"
	text "| Backup directory:              $NAGVIS_PATH_OLD" "|"
	text
	text "| Note: The current NagVis directory will be moved to the backup directory." "|"
	if [ "$REMOVE" = "y" ]; then
		text "|       The backup directory will be removed after successful installation. " "|"
	else
		text "|       The backup directory will be NOT removed after successful installation. " "|"
	fi
	if [ ! "$NAGVIS_VER_OLD" = "UNKNOWN" ]; then
		text "|       Your configuration files will be copied." "|"
		if [ "$INSTALLER_CONFIG_MOD" = "y" ]; then
			text "|       The configuration files will be updated if possible." "|"
		else
			text "|       The configuration files will NOT be updated. Please check the " "|"
			text "|       changelog for any changes which affect your configuration files." "|"
		fi
	else
		text
		text "|          !!! UPDATE FROM VERSION \"$NAGVIS_VER_OLD\" IS NOT SUPPORTED !!!" "|"
		text "|       You have to move your custom files manually from backup directory." "|"
	fi
fi
text

echo "$CALL" >> $LOG
if [ $INSTALLER_QUIET -ne 1 ]; then
	confirm "Do you really want to continue?" "y"
	if [ "$ANS" != "Y" ]; then
		text
		text "| Installer aborted, exiting..." "|"
		line ""
		exit 1
	fi
fi

line ""
text "| Starting installation" "|"
line ""

if [ "$INSTALLER_ACTION" = "update" ]; then
	DONE=`log "Moving old NagVis to $NAGVIS_PATH_OLD.." done` 
	mv $NAGVIS_PATH $NAGVIS_PATH_OLD
	chk_rc "|  Error moving old NagVis $NAGVIS_PATH_OLD" "$DONE"
fi

# Create base path
makedir "$NAGVIS_PATH"

if [ $NAGVIS_TAG -ge 01050000 ]; then
	# Create non shared var directory when not exists
	makedir "$NAGVIS_PATH/var"
	makedir "$NAGVIS_PATH/var/tmpl/cache"
	makedir "$NAGVIS_PATH/var/tmpl/compile"
	# Create shared var directory when not exists
	makedir "$NAGVIS_PATH/share/var"
	# Copy all desired files
	LINE="Copying files to $NAGVIS_PATH..."
	copy "" "share" "$NAGVIS_PATH"
	copy "" "etc" "$NAGVIS_PATH"
	copy "" "LICENCE README" "$NAGVIS_PATH"
	copy "" "docs" "$NAGVIS_PATH/share"
	cmp_js
else
	LINE="Copying files to $NAGVIS_PATH..."
	copy "\\install\.sh$" '*' "$NAGVIS_PATH"
fi

# Remove demo maps if desired
if [ "$IGNORE_DEMO" != "" ]; then
	for i in $IGNORE_DEMO;
	do
		DONE=`log "Removing file(s) $i" done` 
		find $NAGVIS_PATH -name "$i" -exec rm {} \;
		chk_rc "|  Error removing $i" "$DONE"
	done	
fi

# Create main configuration file from sample when no file exists
if [ -f $NAGVIS_PATH/${NAGVIS_CONF}-sample ]; then
	if [ ! -f $NAGVIS_PATH/$NAGVIS_CONF ]; then
		DONE=`log "Creating main configuration file..." done` 
		cp -p $NAGVIS_PATH/${NAGVIS_CONF}-sample $NAGVIS_PATH/$NAGVIS_CONF
		chk_rc "|  Error copying sample configuration" "$DONE"
	fi
fi

# Create apache configuration file from sample when no file exists
if [ -f $NAGVIS_PATH/$HTML_SAMPLE ]; then
	CHG='s/^//'
	if [ -s $WEB_PATH/$HTML_CONF ]; then
		text "| *** $WEB_PATH/$HTML_CONF will NOT be overwritten !" "|"
		HTML_CONF="$HTML_CONF.$DATE"
		text "| *** creating $WEB_PATH/$HTML_CONF instead (commented out config)" "|"
		CHG='s/^/#new /'
	fi
	DONE=`log "Creating web configuration file..." done`
	cat $NAGVIS_PATH/$HTML_SAMPLE | $SED "s#@NAGIOS_PATH@#$NAGIOS_PATH#g;s#@NAGVIS_PATH@#$NAGVIS_PATH#g;s#@NAGVIS_WEB@#$HTML_PATH#g;$CHG" > $WEB_PATH/$HTML_CONF
	chk_rc "|  Error creating web configuration" "$DONE"
	DONE=`log "Setting permissions for web configuration file..." done`
	chown $WEB_USER:$WEB_GROUP $WEB_PATH/$HTML_CONF
	chk_rc "|  Error setting web conf permissions" "$DONE"
fi

text
if [ "$INSTALLER_ACTION" = "update" -a "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
	# Gather path prefixes
	NAGVIS_DIR="nagvis"
	[ $NAGVIS_TAG -ge 01050000 ] && NAGVIS_DIR="share/nagvis"
	USERFILES_DIR="nagvis"
	[ $NAGVIS_TAG -ge 01050000 ] && USERFILES_DIR="share/userfiles"

	LINE="Restoring main configuration file..."
	copy "" "$NAGVIS_CONF" "main configuration file"
	
	LINE="Restoring custom map configuration files..."
	copy "\/(demo\.cfg|demo2\.cfg|demo-server\.cfg|demo-map\.cfg)$" "etc/maps" "map configuration files"
	
	LINE="Restoring custom map images..."
	copy "\/nagvis-demo\.png$" "$USERFILES_DIR/images/maps" "map image files"
	
	LINE="Restoring custom iconsets..."
	copy "\/(20x20\.png|configerror_.+\.png|error\.png|std_.+\.png)$" "$USERFILES_DIR/images/iconsets" "iconset files"
	
	LINE="Restoring custom shapes..."
	copy "" "$USERFILES_DIR/images/shapes" "shapes"
	
	LINE="Restoring custom pages templates..."
	copy "\/default\..+$" "$USERFILES_DIR/templates/pages" "pages templates"
	
	LINE="Restoring custom hover templates..."
	copy "\/tmpl\.default.+$" "$USERFILES_DIR/templates/hover" "hover templates"
	
	LINE="Restoring custom header template images..."
	copy "\/tmpl\.default.+$" "$USERFILES_DIR/images/templates/header" "header template images"

	LINE="Restoring custom hover template images..."
	copy "\/tmpl\.default.+$" "$USERFILES_DIR/images/templates/hover" "hover template images"

	LINE="Restoring custom gadgets..."
	copy "\/(gadgets_core\.php|std_.+\.php)$" "$USERFILES_DIR/gadgets" "gadgets"

	LINE="Restoring custom stylesheets..."
  copy "" "$USERFILES_DIR/styles" "stylesheets"
	
	# Copy auth.db when updating from 1.5x
	if [ $NAGVIS_TAG_OLD -ge 01050000 ]; then
		LINE="Restoring auth database file..."
		copy "" "$NAGVIS_AUTH_DB" "auth database file"
	fi
fi
text

# Do some update tasks (Changing options, notify about deprecated options)
if [ "$INSTALLER_ACTION" = "update" -a "$NAGVIS_VER_OLD" != "UNKNOWN" ]; then
	line "Handling changed/removed options..." "+"
	if [ "x`echo $NAGVIS_VER_OLD | grep '1.3'`" != "x" ]; then
		text "| Update from 1.3.x" "|"
		text
		line "Applying changes to main configuration file..."
		text "| oops, sorry, not implemented yet" "|"
		chk_rc "| Error" "| done"
		line "Applying changes to map configuration files..."
		text "| oops, sorry, not implemented yet" "|"
		chk_rc "| Error" "| done"
	else
		text "| Update from unhandled version $NAGVIS_VER_OLD" "|"
		text
		text "| HINT: Please check the changelog or the documentation for changes which" "|"
		text "|       affect your configuration files" "|"
	fi
fi
text

line "Setting permissions..." "+"
chown $WEB_USER:$WEB_GROUP $NAGVIS_PATH -R
[ -f "$NAGVIS_PATH/$NAGVIS_CONF-sample" ]&&set_perm 664 "$NAGVIS_PATH/$NAGVIS_CONF-sample"
set_perm 775 "$NAGVIS_PATH/etc/maps"
set_perm 664 "$NAGVIS_PATH/etc/maps/*"
# prior to 1.5.x
if [ $NAGVIS_TAG -lt 01050000 ]; then
	set_perm 775 "$NAGVIS_PATH/nagvis/images/maps"
	set_perm 664 "$NAGVIS_PATH/nagvis/images/maps/*"
	set_perm 775 "$NAGVIS_PATH/nagvis/var"
	set_perm 664 "$NAGVIS_PATH/nagvis/var/*"
else
	set_perm 775 "$NAGVIS_PATH/share/userfiles/images/maps"
	set_perm 664 "$NAGVIS_PATH/share/userfiles/images/maps/*"
	set_perm 775 "$NAGVIS_PATH/var"
	set_perm 664 "$NAGVIS_PATH/var/*"
	set_perm 775 "$NAGVIS_PATH/var/tmpl/cache"
	set_perm 775 "$NAGVIS_PATH/var/tmpl/compile"
	set_perm 775 "$NAGVIS_PATH/share/var"
	set_perm 664 "$NAGVIS_PATH/share/var/*"
fi	
text

if [ "$INSTALLER_ACTION" = "update" -a "$REMOVE" = "y" ]; then
    DONE=`log "Removing backup directory" done`
    rm -rf $NAGVIS_PATH_OLD
    chk_rc "|  Error removing directory user configuration" "$DONE"
fi

line
text "| Installation complete" "|"
text
text "| You can safely remove this source directory." "|"
text
text "| What to do next?" "|"
text "| - Read the documentation" "|"
text "| - Maybe you want to edit the main configuration file?" "|"
text "|   Its location is: $NAGVIS_PATH/$NAGVIS_CONF" "|"
text "| - Configure NagVis via browser" "|"
if [ $NAGVIS_TAG -lt 01050000 ]; then
	text "|   <http://localhost${HTML_PATH}/nagvis/config.php>" "|"
else
	text "|   <http://localhost${HTML_PATH}/config.php>" "|"
	text "| - Initial admin credentials:" "|"
	text "|     Username: nagiosadmin" "|"
	text "|     Password: nagiosadmin" "|"
fi
line
exit 0