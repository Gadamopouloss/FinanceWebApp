// variables
let btn = document.querySelector('#new-quote');  // Ensure this matches the updated ID
let quote = document.querySelector('.quote');
let person = document.querySelector('.person');

const quotes = [
    {
        quote: "Do not save what is left after spending, but spend what is left after saving.",
        person: "Warren Buffett"
    },
    {
        quote: "A budget is telling your money where to go instead of wondering where it went.",
        person: "John C. Maxwell"
    },
    {
        quote: "The best investment you can make is in yourself.",
        person: "Warren Buffett"
    },
    {
        quote: "Beware of little expenses; a small leak will sink a great ship.",
        person: "Benjamin Franklin"
    },
    {
        quote: "Do not put all your eggs in one basket.",
        person: "Andrew Carnegie"
    },
    {
        quote: "Debt is like any other trap, easy enough to get into, but hard enough to get out of.",
        person: "Henry Wheeler Shaw"
    },
    { quote: "Compound interest is the eighth wonder of the world. He who understands it, earns it; he who does not, pays it.", person: "Albert Einstein" },
    { quote: "You must gain control over your money, or the lack of it will forever control you.", person: "Dave Ramsey." },
    { quote: "A penny saved is a penny earned.", person: "Benjamin Franklin" },
    { quote: "In investing, what is comfortable is rarely profitable.", person: "Robert Arnott" }
];

btn.addEventListener('click', function() {
    let random = Math.floor(Math.random() * quotes.length);
    quote.innerText = quotes[random].quote;
    person.innerText = quotes[random].person;
});
