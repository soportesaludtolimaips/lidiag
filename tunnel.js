const localtunnel = require('localtunnel');
const fs = require('fs');
const path = require('path');

(async () => {
  try {
    const tunnel = await localtunnel({ port: 80, subdomain: 'lidiag' });
    const msg = `URL: ${tunnel.url}\n`;
    fs.writeFileSync(path.join(__dirname, 'tunnel_url.txt'), msg);
    console.log(msg);

    tunnel.on('close', () => {
      console.log('Tunnel closed');
    });
  } catch (err) {
    const msg = `Error: ${err.message}\n`;
    fs.writeFileSync(path.join(__dirname, 'tunnel_url.txt'), msg);
    console.error(msg);
  }
})();
