#!/bin/bash

# FreeSWITCH Complete Automated Setup Script
# Includes Module Loading, Event Socket, and cURL XML Gateway Bindings

set -e

echo "=========================================="
echo "Starting FreeSWITCH Full Setup..."
echo "=========================================="

# ১. কনফিগারেশন পাথ নির্ধারণ করা
if [ -d "/etc/freeswitch/autoload_configs" ]; then
    AUTOLOAD_DIR="/etc/freeswitch/autoload_configs"
elif [ -d "/usr/local/freeswitch/conf/autoload_configs" ]; then
    AUTOLOAD_DIR="/usr/local/freeswitch/conf/autoload_configs"
else
    AUTOLOAD_DIR="/etc/freeswitch/autoload_configs"
    mkdir -p "$AUTOLOAD_DIR"
fi

echo "Using Autoload Directory: $AUTOLOAD_DIR"

# ২. event_socket.conf.xml তৈরি করা
echo "Configuring event_socket.conf.xml..."
cat << 'EOF' > "$AUTOLOAD_DIR/event_socket.conf.xml"
<configuration name="event_socket.conf" description="Socket Client">
  <settings>
    <param name="listen-ip" value="127.0.0.1"/>
    <param name="listen-port" value="8021"/>
    <param name="password" value="ClueCon"/>
  </settings>
</configuration>
EOF

# ৩. modules.conf.xml তৈরি করা (mod_xml_curl সহ প্রয়োজনীয় মডিউলগুলো লোড করতে)
echo "Configuring modules.conf.xml..."
cat << 'EOF' > "$AUTOLOAD_DIR/modules.conf.xml"
<configuration name="modules.conf" description="Modules">
  <modules>
    <!-- Endpoints -->
    <load module="mod_sofia"/>
    
    <!-- Codecs -->
    <load module="mod_speex"/>
    <load module="mod_ilbc"/>
    <load module="mod_g729"/>
    
    <!-- Applications -->
    <load module="mod_commands"/>
    <load module="mod_dptools"/>
    <load module="mod_ivr"/>
    <load module="mod_valet_parking"/>
    
    <!-- Dialplan & Codecs -->
    <load module="mod_dialplan_xml"/>
    
    <!-- Event Handlers & Dynamic XML -->
    <load module="mod_event_socket"/>
    <load module="mod_xml_curl"/>
    
    <!-- PBX Features -->
    <load module="mod_conference"/>
    <load module="mod_callcenter"/>
  </modules>
</configuration>
EOF

# ৪. xml_curl.conf.xml তৈরি করা (আপনার ৪টি PHP URL যুক্ত করে)
echo "Configuring xml_curl.conf.xml..."
cat << 'EOF' > "$AUTOLOAD_DIR/xml_curl.conf.xml"
<configuration name="xml_curl.conf" description="cURL XML Gateway">
  <bindings>
    <!-- 1. Gateways & Sofia Configuration -->
    <binding name="configuration">
      <param name="gateway-url" value="https://pbx.registercamp.com/freeswitch/fs_gateways.php" bindings="configuration"/>
    </binding>

    <!-- 2. Extensions (Directory) -->
    <binding name="directory">
      <param name="gateway-url" value="https://pbx.registercamp.com/freeswitch/extenson.php" bindings="directory"/>
    </binding>

    <!-- 3. Inbound & Outbound Call Routing (Dialplan) -->
    <binding name="dialplan">
      <param name="gateway-url" value="https://pbx.registercamp.com/freeswitch/Inbound-Outbound.php" bindings="dialplan"/>
    </binding>

    <!-- 4. IVR (Dialplan/Phrases - আপনার ivr.php এর আউটপুট অনুযায়ী বাইন্ডিং সেট করা হলো) -->
    <binding name="ivrdialplan">
      <param name="gateway-url" value="https://pbx.registercamp.com/freeswitch/ivr.php" bindings="dialplan"/>
    </binding>
  </bindings>
</configuration>
EOF

# ৫. পারমিশন ঠিক করা
echo "Setting up proper permissions..."
chown -R freeswitch:freeswitch /etc/freeswitch 2>/dev/null || true
chown -R freeswitch:freeswitch /usr/local/freeswitch 2>/dev/null || true

echo "=========================================="
echo "FreeSWITCH Full Setup completed successfully!"
echo "=========================================="
