import * as paginationFunctions from "./pagination.js";

const productsGrid = document.querySelector("#products-grid");
productsGrid.innerText = "";

const paginationDiv = document.querySelector("#pagination");

const getProductCardHtml = async () => {
  const cardUrl = "../html_components/phone_card.html";
  const response = await fetch(cardUrl);
  if (!response.ok) {
    throw new Error("Network response was not ok");
  }
  return response.text();
};

let page_nr = 1;
let documentWidth = window.innerWidth;
// default value
let items_per_page = 8;
documentWidth >= 768 ? (items_per_page = 8) : (items_per_page = 4);

sessionStorage.setItem(
  "products_count",
  parseInt(paginationDiv.getAttribute("data-total-products"))
);

const renderProductsPage = (page_nr, items_per_page) => {
  const client_request = "get_data_portion";
  const requested_data = "products";

  const promises = [
    getProductCardHtml(),
    paginationFunctions.dataFetcher(
      client_request,
      requested_data,
      page_nr,
      items_per_page
    )
  ];

  Promise.all(promises).then(([cardHtml, productsData]) => {
    const parser = new DOMParser();
    productsData.forEach((product) => {
      const parsedCard = parser.parseFromString(cardHtml, "text/html");
      const nextCard = parsedCard.querySelector(".phone-card");

      nextCard.classList.remove("dark:bg-bg-dark");
      nextCard.classList.remove("bg-bg-lighter");
      nextCard.classList.add("dark:bg-bg-darker");
      nextCard.classList.add("bg-bg-light");

      nextCard
        .querySelector("a")
        .setAttribute("href", `product.php?id=${product.product_id}`);
      nextCard.querySelector(".promo-sticker").classList.add("hidden");
      nextCard
        .querySelector("img.insert-product-img")
        .setAttribute("src", `../res/phones/${product.product_img_path}`);
      nextCard.querySelector("p.insert-product-name").innerText =
        product.product_name;
      nextCard.querySelector("p.insert-product-price").innerText =
        product.product_price;
      nextCard.setAttribute("data-product-id", product.product_id);

      productsGrid.appendChild(nextCard);
    });

    const paginationTotalPages = paginationDiv.querySelector("p.total-pages");
    const savedProductsCount = sessionStorage.getItem("products_count");
    const totalPagesCount = Math.ceil(savedProductsCount / items_per_page);

    paginationTotalPages.innerText = totalPagesCount;
  });
};
renderProductsPage(page_nr, items_per_page);

// handle pagination buttons

const paginationFirst = paginationDiv.querySelector(
  "button.pagination-btn-first"
);
const paginationPrev = paginationDiv.querySelector(
  "button.pagination-btn-prev"
);
const paginationNext = paginationDiv.querySelector(
  "button.pagination-btn-next"
);
const paginationLast = paginationDiv.querySelector(
  "button.pagination-btn-last"
);
const paginationCurrentPage = paginationDiv.querySelector("p.current-page");
sessionStorage.setItem("page_nr", page_nr);

// HACK:
// users total cannot arrive as async call result because calculation of pagination pages
// will be broken on initial load no matter what (cannot move the pagination code inside
// the promise chain block, for some reason it throws me into recursive callbak  hell
// and I cannot be arsed to figure out why for this demo project at this stage
// of being sick with it) The value is passed in the markup as data atribute
// avoiding async delays, allows for 100% foolproof render of pagination on initial load.
// Worse case web scrapers will learn that I have 10 fake users on my demo  site XD
sessionStorage.setItem(
  "products_count",
  parseInt(paginationDiv.getAttribute("data-total-products"))
);

const totalPagesCount = Math.ceil(
  sessionStorage.getItem("products_count") / items_per_page
);
const cardsClass = "phone-card";

paginationFirst.addEventListener("click", () => {
  paginationFunctions.removeCurrentlyRenderedCardsFromDom(
    productsGrid,
    cardsClass
  );
  page_nr = 1;
  sessionStorage.setItem("page_nr", page_nr);
  renderProductsPage(page_nr, items_per_page);
  paginationCurrentPage.innerText = page_nr;
  paginationFunctions.customEnableBtn(paginationNext);
  paginationFunctions.customDisableBtn(paginationPrev);
  paginationFunctions.customEnableBtn(paginationLast);
  paginationFunctions.customDisableBtn(paginationFirst);
});

paginationPrev.addEventListener("click", () => {
  let currentPageNr = parseInt(sessionStorage.getItem("page_nr"));
  if (currentPageNr > 1) {
    paginationFunctions.removeCurrentlyRenderedCardsFromDom(
      productsGrid,
      cardsClass
    );
    page_nr = currentPageNr - 1;
    sessionStorage.setItem("page_nr", page_nr);
    renderProductsPage(page_nr, items_per_page);
    paginationCurrentPage.innerText = page_nr;
    paginationFunctions.customEnableBtn(paginationLast);
    paginationFunctions.customEnableBtn(paginationNext);
  } else {
    paginationFunctions.customEnableBtn(paginationNext);
    paginationFunctions.customDisableBtn(paginationPrev);
    paginationFunctions.customEnableBtn(paginationLast);
    paginationFunctions.customDisableBtn(paginationFirst);
  }
});

paginationNext.addEventListener("click", () => {
  let currentPageNr = parseInt(sessionStorage.getItem("page_nr"));
  if (currentPageNr < totalPagesCount) {
    paginationFunctions.removeCurrentlyRenderedCardsFromDom(
      productsGrid,
      cardsClass
    );
    page_nr = currentPageNr + 1;
    sessionStorage.setItem("page_nr", page_nr);
    renderProductsPage(page_nr, items_per_page);
    paginationCurrentPage.innerText = page_nr;
    paginationFunctions.customEnableBtn(paginationFirst);
    paginationFunctions.customEnableBtn(paginationPrev);
  } else {
    paginationFunctions.customDisableBtn(paginationNext);
    paginationFunctions.customEnableBtn(paginationPrev);
    paginationFunctions.customDisableBtn(paginationLast);
    paginationFunctions.customEnableBtn(paginationFirst);
  }
});

paginationLast.addEventListener("click", () => {
  paginationFunctions.removeCurrentlyRenderedCardsFromDom(
    productsGrid,
    cardsClass
  );
  page_nr = totalPagesCount;
  sessionStorage.setItem("page_nr", page_nr);
  renderProductsPage(page_nr, items_per_page);
  paginationCurrentPage.innerText = page_nr;
  paginationFunctions.customDisableBtn(paginationNext);
  paginationFunctions.customEnableBtn(paginationPrev);
  paginationFunctions.customDisableBtn(paginationLast);
  paginationFunctions.customEnableBtn(paginationFirst);
});

paginationCurrentPage.addEventListener("DOMSubtreeModified", () => {
  let currentPageNr = parseInt(sessionStorage.getItem("page_nr"));
  if (currentPageNr == 1) {
    paginationFunctions.customDisableBtn(paginationPrev);
    paginationFunctions.customDisableBtn(paginationFirst);
  } else if (currentPageNr == totalPagesCount) {
    paginationFunctions.customDisableBtn(paginationNext);
    paginationFunctions.customDisableBtn(paginationLast);
  }
});

// page 1 on load so disable those by default
paginationFunctions.customDisableBtn(paginationPrev);
paginationFunctions.customDisableBtn(paginationFirst);
