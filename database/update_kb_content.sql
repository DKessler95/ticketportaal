-- Update KB articles with HTML content

UPDATE knowledge_base SET content = '<h1>VPN Connection Guide</h1>
<p>To connect to the Kruit &amp; Kramer VPN:</p>
<h2>Windows</h2>
<ol>
<li>Click the network icon in the system tray</li>
<li>Select "VPN" from the menu</li>
<li>Choose "Kruit &amp; Kramer VPN"</li>
<li>Enter your company credentials</li>
<li>Click "Connect"</li>
</ol>
<h2>Mac</h2>
<ol>
<li>Open System Preferences</li>
<li>Click "Network"</li>
<li>Select the VPN connection</li>
<li>Click "Connect"</li>
<li>Enter your credentials</li>
</ol>
<p>If you experience connection issues, ensure you have the latest VPN client installed. Contact ICT support for assistance.</p>' 
WHERE kb_id = 3;

UPDATE knowledge_base SET content = '<h1>Printer Troubleshooting Guide</h1>
<p>Before creating a ticket, try these steps:</p>
<ol>
<li><strong>Check Power</strong>: Ensure the printer is turned on and plugged in</li>
<li><strong>Check Connection</strong>: Verify the USB or network cable is connected</li>
<li><strong>Check Paper</strong>: Make sure there is paper in the tray</li>
<li><strong>Check Ink/Toner</strong>: Verify ink or toner levels</li>
<li><strong>Restart Printer</strong>: Turn off the printer, wait 30 seconds, turn it back on</li>
<li><strong>Check Print Queue</strong>: Clear any stuck print jobs on your computer</li>
<li><strong>Restart Computer</strong>: Sometimes a simple restart resolves the issue</li>
</ol>
<p>If none of these steps work, create a ticket with details about the error message or problem.</p>'
WHERE kb_id = 1;
