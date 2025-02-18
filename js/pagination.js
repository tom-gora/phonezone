export const dataFetcher = async (
  client_request,
  requested_data,
  page_nr,
  items_per_page
) => {
  const data = {
    client_request: client_request,
    requested_data: requested_data,
    page_nr: page_nr,
    items_per_page: items_per_page
  };

  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = "../scripts/get_paginated_data.php";
  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .catch((error) => {
      console.log(
        "There has been a problem with your fetch operation: " + error.message
      );
    });
};

export const customDisableBtn = (btn) => {
  btn.setAttribute("inert", "");
  btn.setAttribute("disabled", true);
  btn.style.filter = "brightness(0.5)";
};

export const customEnableBtn = (btn) => {
  btn.removeAttribute("inert");
  btn.removeAttribute("disabled");
  btn.style.filter = "brightness(1)";
};

export const removeCurrentlyRenderedCardsFromDom = (
  productsGrid,
  cardClass
) => {
  const currentlyRenderedCards = productsGrid.querySelectorAll(`.${cardClass}`);
  currentlyRenderedCards.forEach((card) => {
    card.parentNode.removeChild(card);
  });
};
