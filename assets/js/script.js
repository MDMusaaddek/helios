
document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".tab-btn");
    const cards = Array.from(document.querySelectorAll(".product-card"));
    const viewMoreBtn = document.getElementById("viewMoreBtn");
    const noProducts = document.getElementById("noProducts");
    const categoryLinks = document.querySelectorAll("[data-nav-category]");

    let activeCategory = "Men";
    let expanded = false;
    const initialLimit = 6;

    function renderProducts() {
        const matchingCards = cards.filter(function (card) {
            return card.dataset.category === activeCategory;
        });

        cards.forEach(function (card) {
            const categoryMatches = card.dataset.category === activeCategory;
            const index = matchingCards.indexOf(card);

            card.hidden = !categoryMatches ||
                (!expanded && index >= initialLimit);
        });

        tabs.forEach(function (tab) {
            const selected = tab.dataset.category === activeCategory;

            tab.classList.toggle("active", selected);
            tab.setAttribute("aria-selected", String(selected));
        });

        noProducts.hidden = matchingCards.length > 0;

        viewMoreBtn.hidden = matchingCards.length <= initialLimit;
        viewMoreBtn.textContent = expanded ? "SHOW LESS" : "VIEW MORE";
    }

    function selectCategory(category) {
        activeCategory = category;
        expanded = false;
        renderProducts();
    }

    tabs.forEach(function (tab) {
        tab.addEventListener("click", function () {
            selectCategory(tab.dataset.category);
        });
    });

    categoryLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            selectCategory(link.dataset.navCategory);
        });
    });

    viewMoreBtn.addEventListener("click", function () {
        expanded = !expanded;
        renderProducts();
    });

    renderProducts();
});
