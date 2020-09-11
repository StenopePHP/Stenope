const http = require('http');
const Prism = require('prismjs');
const loadLanguages = require('prismjs/components/');
const [host, port] = process.argv.slice(2);

function onListening() {
    process.stdout.write(`\x1b[42m Listening on http://${host}:${port}...\x1b[0m`);
}

function onError(error) {
    process.stderr.write(`\x1b[41m ${error.toString()}\x1b[0m\n`, () => process.exit(1));
}

function onRequest(request, response) {
    const body = [];

    request.on('data', chunk => body.push(chunk))
    request.on('end', () => {
        let data;

        try {
            const { language, value } = JSON.parse(body);

            loadLanguages([language]);

            response.writeHead(200, { 'Content-Type': 'text/html' });
            response.end(Prism.highlight(value, Prism.languages[language], language));
        } catch (error) {
            response.writeHead(400);
            response.end();

            return;
        }
    })
}

const server = http.createServer();

server.on('listening', onListening);
server.on('request', onRequest);
server.on('error', onError);

server.listen(port, host);
