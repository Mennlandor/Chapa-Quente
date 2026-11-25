const purchaseForm = document.querySelector('[data-preco]');
if (purchaseForm) {
    const quantityInput = purchaseForm.querySelector('#quantity');
    const decreaseButton = purchaseForm.querySelector('[data-passo="-1"]');
    const increaseButton = purchaseForm.querySelector('[data-passo="1"]');
    const totalLabel = purchaseForm.querySelector('[data-total]');
    const currencyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function updateTotal() {
        const units = Number(quantityInput.value) || 1;
        decreaseButton.disabled = units <= 1;
        increaseButton.disabled = units >= 99;
        totalLabel.textContent = '• ' + currencyFormatter.format(Number(purchaseForm.dataset.preco) * units / 100);
    }

    decreaseButton.addEventListener('click', () => {
        quantityInput.stepDown();
        updateTotal();
    });
    increaseButton.addEventListener('click', () => {
        quantityInput.stepUp();
        updateTotal();
    });
    quantityInput.addEventListener('input', updateTotal);
}

const searchInput = document.querySelector('.search input[name="busca"]');
const emptyMessage = document.querySelector('#search-empty');
const suggestions = document.querySelector('#search-suggestions');

if (searchInput && emptyMessage && suggestions) {
    const productCards = document.querySelectorAll('.product-card');
    const categoryLinks = document.querySelectorAll('.categories a');
    const searchField = searchInput.closest('.search-field');

    function updateSearch(showSuggestions = true) {
        const query = searchInput.value.trim();
        const searchText = query.toLocaleLowerCase('pt-BR');
        const names = new Set();
        let matches = 0;

        productCards.forEach((card) => {
            const name = card.querySelector('h3').textContent.trim();
            card.hidden = !name.toLocaleLowerCase('pt-BR').includes(searchText);
            if (!card.hidden) {
                matches++;
                if (query && names.size < 5) names.add(name);
            }
        });
        emptyMessage.hidden = matches > 0;

        categoryLinks.forEach((link) => {
            const url = new URL(link.href);
            url.searchParams.set('busca', query);
            link.href = url.href;
        });

        suggestions.replaceChildren();
        if (showSuggestions) {
            names.forEach((name) => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = name;
                item.append(button);
                suggestions.append(item);
            });
        }
        suggestions.hidden = !suggestions.children.length;
    }

    searchInput.addEventListener('input', () => updateSearch());
    searchInput.addEventListener('focus', () => updateSearch());
    searchField.addEventListener('focusout', (event) => {
        if (!searchField.contains(event.relatedTarget)) suggestions.hidden = true;
    });

    suggestions.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;
        searchInput.value = button.textContent;
        searchInput.focus();
        updateSearch(false);
    });

    searchField.addEventListener('keydown', (event) => {
        if (suggestions.hidden) return;
        if (event.key === 'ArrowDown' && event.target === searchInput) {
            event.preventDefault();
            suggestions.querySelector('button')?.focus();
        }
        if (event.key === 'Escape') {
            searchInput.focus();
            suggestions.hidden = true;
        }
    });
}
