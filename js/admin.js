import * as dialogFunctions from "./dialog_functions.js";
import * as paginationFunctions from "./pagination.js";

// popup for errors and status messages
const msgBox = document.querySelector("#msg-box");
//dialog boxes
const editDialog = document.querySelector("#edit-dialog");
const delDialog = document.querySelector("#del-dialog");
// trigger buttons
const addUserButton = document.querySelector("#add-user-btn");
const delButtons = document.querySelectorAll(".del-btn");
// close dialog buttons
const editDialogCancelBtn = editDialog.querySelector("#edit-dialog-close");
const delDialogCancelBtn = document.querySelector("#del-dialog-close");
// other modified elements
const imageInput = editDialog.querySelector("input[name='edit-user-image']");
const imageInputLabel = editDialog.querySelector(
  "label[for='edit-user-image']"
);
const imageLabelBg = editDialog.querySelector("#label-bg");
const imageLabelDesc = editDialog.querySelector("#user-img-description");
const imageLabelSvg = editDialog.querySelector(
  "label[for='edit-user-image'] svg"
);
const delInput = delDialog.querySelector("input[value='delete_user']");
const delDialogConfirmationBox = delDialog.querySelector(
  "#del-confirmation-box"
);
// pagination
const paginationDiv = document.querySelector("#pagination");

// url query string for error and status messages
const params = new URLSearchParams(window.location.search);
const err = params.get("error");
const stat = params.get("status");
const err_msg = params.get("err_msg");

//error handling and status messages
//logically admin is the one on the page so no need to do
//"contact the admin flow"

switch (err) {
  //
  //WARN:
  // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  // ASSESSMENT: this is adding task 3's final touch. UI feedback on error,
  // should a disallowed operation be attempted somehow
  // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  //
  case "deletedisallowed":
    msgBox.innerText = "You are not allowed to delete this user.";
    msgBox.classList.remove("bg-bg-info]");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  case "editdisallowed":
    msgBox.innerText = "You are not allowed to edit this user.";
    msgBox.classList.remove("bg-bg-info]");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  // and other cases
  case "add_emailtaken":
    msgBox.innerText = "Cannot add user. Email already taken.";
    msgBox.classList.remove("bg-bg-info]");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  case "internalerr":
    const current_timestamp = new Date().toLocaleString();
    msgBox.innerHTML = `<p>Something went wrong.<br>Error: ${err_msg}<br>Timestamp: ${current_timestamp} </p>`;
    msgBox.classList.remove("animate-delay-[3000]");
    msgBox.style.animationDelay = "8000ms";
    msgBox.classList.remove("bg-bg-info]");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
}

// status messages
switch (stat) {
  case "userdeleted":
    msgBox.innerText = "User has been deleted.";
    msgBox.classList.remove("hidden");
    break;
  case "userupdated":
    msgBox.innerText = "User has been updated.";
    msgBox.classList.remove("hidden");
    break;
  case "useradded":
    msgBox.innerText = "User has been added.";
    msgBox.classList.remove("hidden");
    break;
}

//listeners

// src: https://www.stefanjudis.com/blog/a-look-at-the-dialog-elements-super-powers/
delDialog.onclick = (e) => {
  if (e.target.nodeName === "DIALOG") {
    delDialog.close();
  }
};

editDialog.onclick = (e) => {
  if (e.target.nodeName === "DIALOG") {
    dialogFunctions.clearEditForm(editDialog);
    editDialog.close();
  }
};

delDialogCancelBtn.addEventListener("click", (e) => {
  e.preventDefault();
  delDialog.close();
});

imageInput.addEventListener("change", () => {
  if (imageInput.files[0] && imageInput.files[0].name) {
    const reader = new FileReader();

    reader.onload = (e) => {
      const fileUrl = e.target.result;

      imageLabelDesc.classList.add("hidden");
      imageInputLabel.classList.remove("grid-rows-2");
      imageLabelSvg.classList.add("hidden");
      imageLabelBg.style.backgroundImage = `url(${fileUrl})`;
      imageLabelBg.classList.remove("opacity-25");
    };
    reader.readAsDataURL(imageInput.files[0]);
  }
});

editDialog.addEventListener("close", () => {
  dialogFunctions.clearEditForm(editDialog);
});

editDialogCancelBtn.addEventListener("click", (e) => {
  e.preventDefault();
  dialogFunctions.clearEditForm(editDialog);
  editDialog.close();
});

addUserButton.addEventListener("click", () => {
  dialogFunctions.setFormForAddition(editDialog);
  editDialog.showModal();
  editDialog.showModal();
  editDialog.showModal();
  editDialog.showModal();
});

const usersGrid = document.querySelector("#users-grid");

const getUserCardHtml = async () => {
  const cardUrl = "../html_components/admin_user_card.html";
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
documentWidth >= 768 ? (items_per_page = 8) : (items_per_page = 3);

const renderUsersPage = (page_nr, items_per_page) => {
  const client_request = "get_data_portion";
  const requested_data = "users";

  const promises = [
    getUserCardHtml(),
    paginationFunctions.dataFetcher(
      client_request,
      requested_data,
      page_nr,
      items_per_page
    ),
    dialogFunctions.get_role()
  ];

  Promise.all(promises).then(([cardHtml, usersData, loggedInUserRole]) => {
    //for dynamic front end pagination reimplemented conditional logic previously
    // handled by php on the server when all users were loaded at once
    //
    //WARN:
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // ASSESSMENT: this improves the restrictions by hiding select options
    // and not allowing to add or edit admin/owner types unless and owner account
    // is logged in. regular admin type can only add regular user types
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    const roleSelect = document.querySelector("#edit-user-type");

    if (loggedInUserRole !== "owner" && roleSelect.children.length > 1) {
      roleSelect.querySelector("option[value='admin']").remove();
      roleSelect.querySelector("option[value='owner']").remove();
    }

    const parser = new DOMParser();
    // extract the total number of users passed as the last element of arr
    // then dispose of it to only leave actual user data

    usersData.forEach((user) => {
      const parsedCard = parser.parseFromString(cardHtml, "text/html");
      const nextCard = parsedCard.querySelector(".admin-page-user-card");
      const nextCardButtons = nextCard.querySelectorAll("button");
      const nextCardForbiddenImg = nextCard.querySelector(".forbidden");

      // additionally remove edit/delete buttons entirely from dom
      // for non-regular user cards unless the owner is logged in
      if (loggedInUserRole !== "owner" && user.user_type !== "User") {
        nextCardForbiddenImg.classList.remove("hidden");
        nextCardButtons.forEach((btn) => {
          btn.parentNode.removeChild(btn);
        });
      }

      nextCard.querySelector("p.insert-user-id").innerText = user.user_id;
      nextCard.querySelector("p.insert-user-name").innerText =
        `${user.user_firstname} ${user.user_lastname}`;
      nextCard.querySelector("p.insert-user-email").innerText = user.user_email;
      nextCard.querySelector("p.insert-user-registration").innerText =
        user.user_registration;
      nextCard.querySelector("p.insert-user-role").innerText = user.user_type;
      nextCard.querySelector("p.insert-user-auth").innerText =
        user.user_auth_method;
      const editBtn = nextCard.querySelector(".edit-btn");
      const delBtn = nextCard.querySelector(".del-btn");
      if (editBtn) {
        editBtn.dataset.userId = user.user_id;
        editBtn.dataset.userFirstName = user.user_firstname;
        editBtn.dataset.userLastName = user.user_lastname;
        editBtn.dataset.userEmail = user.user_email;
        editBtn.dataset.userImg = user.user_img;
        editBtn.dataset.userType = user.user_type;

        editBtn.addEventListener("click", () => {
          //grab prefilled values stored on the button itself
          const id = editBtn.dataset.userId;
          const firstName = editBtn.dataset.userFirstName;
          const lastName = editBtn.dataset.userLastName;
          const email = editBtn.dataset.userEmail;
          const img = editBtn.dataset.userImg;
          const role = editBtn.dataset.userType;
          dialogFunctions.prefillFormForEdit(
            editDialog,
            id,
            firstName,
            lastName,
            email,
            img,
            role
          );
          editDialog.showModal();
        });
      }
      if (delBtn) {
        delBtn.setAttribute("data-user-id", user.user_id);
        delBtn.setAttribute(
          "data-user-name",
          `${user.user_firstname} ${user.user_lastname}`
        );

        delBtn.addEventListener("click", () => {
          const id = delBtn.dataset.userId;
          const name = delBtn.dataset.userName;
          delInput.value = id;
          delDialogConfirmationBox.innerText = `Are you sure you want to delete ${name}?`;

          delDialog.showModal();
        });
      }

      usersGrid.appendChild(nextCard);
    });

    const paginationTotalPages = paginationDiv.querySelector("p.total-pages");
    const savedUserCount = sessionStorage.getItem("user_count");
    const totalPagesCount = Math.ceil(savedUserCount / items_per_page);
    paginationTotalPages.innerText = totalPagesCount;
  });
};

renderUsersPage(page_nr, items_per_page);

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
  "user_count",
  parseInt(paginationDiv.getAttribute("data-total-users"))
);

const totalPagesCount = Math.ceil(
  sessionStorage.getItem("user_count") / items_per_page
);

const cardsClass = "admin-page-user-card";

paginationFirst.addEventListener("click", () => {
  paginationFunctions.removeCurrentlyRenderedCardsFromDom(
    usersGrid,
    cardsClass
  );
  page_nr = 1;
  sessionStorage.setItem("page_nr", page_nr);
  renderUsersPage(page_nr, items_per_page);
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
      usersGrid,
      cardsClass
    );
    page_nr = currentPageNr - 1;
    sessionStorage.setItem("page_nr", page_nr);
    renderUsersPage(page_nr, items_per_page);
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
      usersGrid,
      cardsClass
    );
    page_nr = currentPageNr + 1;
    sessionStorage.setItem("page_nr", page_nr);
    renderUsersPage(page_nr, items_per_page);
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
    usersGrid,
    cardsClass
  );
  page_nr = totalPagesCount;
  sessionStorage.setItem("page_nr", page_nr);
  renderUsersPage(page_nr, items_per_page);
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
