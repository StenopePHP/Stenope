const http = require('http');
const Prism = require('prismjs');
const loadLanguages = require('prismjs/components/');
const [host, port] = process.argv.slice(2);

function requestHandler(request, response) {
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

const server = http.createServer(requestHandler);

server.listen(port, host);

console.info(`Listening on http://${host}:${port}.`);
