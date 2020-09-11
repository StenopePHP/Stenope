const readline = require('readline');
const Prism = require('prismjs');
const loadLanguages = require('prismjs/components/');

function onInput(input) {
    try {
        const { language, value } = JSON.parse(input);

        loadLanguages([language]);

        const result = Prism.highlight(value, Prism.languages[language], language);

        process.stdout.write(result);
    } catch (error) {
        process.stderr.write(error.toString());
    }
}

const server = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
    terminal: false,
});

server.on('line', onInput);
