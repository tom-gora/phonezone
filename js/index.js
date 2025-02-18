const msgBox = document.querySelector("#msg-box");
const loginDialog = document.querySelector("#login-dialog");
const loginErrMsgBox = document.querySelector("#err-msg");

const params = new URLSearchParams(window.location.search);
const err = params.get("error");
const stat = params.get("status");
let err_msg;
// I was inconsistent but fuck it handle both cases of wording I might have used -_-
params.get("error_msg")
  ? (err_msg = params.get("error_msg"))
  : (err_msg = params.get("err_msg"));

const heroSection = document.querySelector("#hero-section");
const heroItemsWrapper = heroSection.querySelector("#hero-items-wrapper");
const heroPrevBtn = heroSection.querySelector("#hero-prev-btn");
const heroNextBtn = heroSection.querySelector("#hero-next-btn");

const getHeroContents = async (client_request) => {
  const data = {
    client_request: client_request
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json, charset=utf-8"
    },
    body: JSON.stringify(data)
  };
  const url = "./scripts/get_hero_contents.php";
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

const displayDuration = 10000;
let animationFrameId = null;
let isAutoPlayEnabled = true;
let lastUpdateTime = null;
let userInteractionTimeout = null;

getHeroContents("get_hero_contents").then((data) => {
  let currentHeroItem = data.length - 1;
  let heroItems = data.map((item) => {
    const parser = new DOMParser();
    const nextHtml = `
    <div data-item-id="${data.indexOf(item)}" class="hero-item grid grid-cols-1 md:grid-rows-1 md:grid-cols-2 grid-rows-10 md:mr-8 md:justify-end">
      <!-- bg image as inline style for easier js manipulation -->
      <div
        style="--duration: ${displayDuration / 1000}s"
        class="hero-card-wrapper mx-auto mb-8 md:mb-0 columns-1 row-span-6 relative aspect-[3/4] w-64">
        <div
          style="background-image: url('${item.hero_card_img}');"
          class="hero-card custom-animated absolute inset-0 z-[1] rounded-sm bg-cover px-8 py-8 text-center before:absolute before:-bottom-1 before:-right-1 before:left-1 before:top-1 before:z-[-1] before:rounded-sm before:bg-brand-secondary before:content-[''] after:absolute after:inset-2 after:z-[2] after:border-2 after:border-brand-secondary after:bg-[rgba(255,255,255,0.4)] after:content-[''] dark:before:bg-bg-lighter dark:after:border-bg-lighter dark:after:bg-[rgba(0,0,0,0.4)]">
          <div
            class="hero-card-front flex h-full flex-col justify-between text-brand-secondary dark:text-bg-lighter">
            <h3 class="text-3xl">${item.hero_card_title}</h3>
            <p class="text-sm">${item.hero_card_subtitle}</p>
          </div>
        </div>
      </div>
    <div class="flex gap-4 self-end h-full col-span-1 row-span-4 max-w-lg flex-col justify-between md:ml-8">
      <div
        style="--duration: ${displayDuration / 1000}s"
        class="hero-description custom-animated mx-auto w-10/12 self-start md:max-w-96 text-pretty px-8 pr-4 md:ml-0 md:mt-8 md:px-0"><p>${item.hero_description}</div>
      <button
        type="button"
        class="btn-primary dark:btn-neutral mb-8 w-fit self-center justify-self-start md:self-start">
        <span></span>
        <span></span>
        <span>Dummy Button</span>
      </button>
  </div>
</div>`;
    return parser.parseFromString(nextHtml, "text/html").body.firstChild;
  });
  heroItems.forEach((item) => {
    item.classList.add("hidden");
    heroItemsWrapper.appendChild(item);
  });

  heroPrevBtn.addEventListener("click", () => {
    handleUserInteraction();
    showPrevHeroItem();
  });

  heroNextBtn.addEventListener("click", () => {
    handleUserInteraction();
    showNextHeroItem();
  });

  function showPrevHeroItem() {
    heroItems[currentHeroItem].classList.add("hidden");
    currentHeroItem =
      (currentHeroItem - 1 + heroItems.length) % heroItems.length;
    heroItems[currentHeroItem].classList.remove("hidden");
    lastUpdateTime = performance.now();
  }

  function showNextHeroItem() {
    heroItems[currentHeroItem].classList.add("hidden");
    currentHeroItem = (currentHeroItem + 1) % heroItems.length;
    heroItems[currentHeroItem].classList.remove("hidden");
    lastUpdateTime = performance.now();
  }

  function handleUserInteraction() {
    isAutoPlayEnabled = false;
    clearTimeout(userInteractionTimeout);
    userInteractionTimeout = setTimeout(() => {
      isAutoPlayEnabled = true;
    }, displayDuration);
  }

  function autoPlayHeroItems(currentTime) {
    if (isAutoPlayEnabled) {
      if (!lastUpdateTime || currentTime - lastUpdateTime >= displayDuration) {
        showNextHeroItem();
      }
    }
    animationFrameId = requestAnimationFrame(autoPlayHeroItems);
  }

  autoPlayHeroItems(performance.now());
});

//status handling currently only for logout
switch (stat) {
  case "order_placed":
    msgBox.innerText = "Your order has been placed. Thank you!";
    msgBox.classList.remove("hidden");
    break;
  case "userdeleted":
    msgBox.innerHTML = "Your account has been deleted.<br>Goodbye!";
    msgBox.classList.remove("hidden");
    break;
  case "loggedout":
    msgBox.innerText = "You have been logged out.";
    msgBox.classList.remove("hidden");
    break;
  case "userregistered":
    msgBox.innerText = "Registered successfully. You can now log in.";
    msgBox.classList.remove("hidden");
    loginDialog.showModal();
    break;
}

let msg, current_timestamp, current_id;
current_id = sessionStorage.getItem("user_id");

// full error handling with contact link and error message passed into email
if (err_msg) {
  // if specific error message is passed then set the message
  // just ready to send at a click of a button
  switch (err) {
    case "internalerr":
      msg = "Something went wrong. Please";
      break;
    case "fatalinvalidid":
      msg = "Failed processing user ID. Please";
      break;
    case "users_constraint":
      msg =
        "<p>This is a demo system.<br>Limit of registered users has been reached.<br>If you have an account consider removing it to add a new one.<br>Otherwise";
      break;
    default:
      msg = "Unknown error. Please";
  }
}

switch (err) {
  case "fatalinvalidid":
    current_timestamp = new Date().toLocaleString();
    msgBox.innerHTML = `<p>${msg} <a class="email-admin"
          href="mailto:goratomasz@outlook.com?subject=PHONEZONE%20:%20Error%20report%20from%20user%20${encodeURIComponent(current_id)}&body=---%0AError%20code:%0A${encodeURIComponent(err_msg)}%0AOccurred%20at:%0A${encodeURIComponent(current_timestamp)}%0A---%0A%0A⚠️%20Do%20not%20delete%20the%20error%20code%20above!%20⚠️">contact the administrator</a>.</p>`;
    msgBox.classList.remove("animate-delay-[3000]");
    // HACK: changing animation via toggling classes don't work so forcing longer
    // delay directly with JS to allow for re3ading longer message and clicking link
    msgBox.style.animationDelay = "8000ms";
    msgBox.style.animationDelay = "8000ms";
    msgBox.classList.remove("bg-bg-info");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  case "users_constraint":
    current_timestamp = new Date().toLocaleString();
    msgBox.innerHTML = `<p>${msg} <a class="email-admin"
          href="mailto:goratomasz@outlook.com?subject=PHONEZONE%20:%20Error%20report%20from%20user%20${encodeURIComponent(current_id)}&body=---%0AError%20code:%0A${encodeURIComponent(err_msg)}%0AOccurred%20at:%0A${encodeURIComponent(current_timestamp)}%0A---%0A%0A⚠️%20Do%20not%20delete%20the%20error%20code%20above!%20⚠️">contact the administrator</a>.</p>`;
    msgBox.classList.remove("animate-delay-[3000]");
    // HACK: changing animation via toggling classes don't work so forcing longer
    // delay directly with JS to allow for re3ading longer message and clicking link
    msgBox.style.animationDelay = "8000ms";
    msgBox.style.animationDelay = "8000ms";
    msgBox.classList.remove("bg-bg-info");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  case "internalerr":
    current_timestamp = new Date().toLocaleString();
    msgBox.innerHTML = `<p>${msg} <a class="email-admin"
          href="mailto:goratomasz@outlook.com?subject=PHONEZONE%20:%20Error%20report%20from%20user%20${encodeURIComponent(current_id)}&body=---%0AError%20code:%0A${encodeURIComponent(err_msg)}%0AOccurred%20at:%0A${encodeURIComponent(current_timestamp)}%0A---%0A%0A⚠️%20Do%20not%20delete%20the%20error%20code%20above!%20⚠️">contact the administrator</a>.</p>`;
    msgBox.classList.remove("animate-delay-[3000]");
    msgBox.style.animationDelay = "8000ms";
    msgBox.classList.remove("bg-bg-info");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;

  // info level error reporting more generic with no specific admin email
  case "autherror":
    msgBox.innerHTML =
      "<p>Something went wrong. Please try a different login method <br>or <a class='email-admin' href='mailto:goratomasz@outlook.com'>contact the administrator</a>.</p>";
    msgBox.classList.remove("animate-delay-[3000]");
    msgBox.style.animationDelay = "8000ms";
    msgBox.classList.remove("bg-bg-info");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  case "nouser":
    loginErrMsgBox.innerText = "No user with that email and password.";
    loginErrMsgBox.classList.toggle("hidden");
    loginDialog.showModal();
    break;
  case "nologin":
    loginErrMsgBox.innerText = "You need to log in to see this page.";
    loginErrMsgBox.classList.toggle("hidden");
    loginDialog.showModal();
    break;
  case "noadmin":
    msgBox.innerText = "You have no permissions to access this page.";
    msgBox.classList.remove("bg-bg-info");
    msgBox.classList.add("bg-brand-primary-200");
    msgBox.classList.remove("hidden");
    break;
  case "passnotmatch":
    registerErrMsgBox.innerText = "Passwords do not match.";
    registerErrMsgBox.classList.toggle("hidden");
    registerDialog.showModal();
    break;
  case "registeremailtaken":
    registerErrMsgBox.innerText = "Email is already taken.";
    registerErrMsgBox.classList.toggle("hidden");
    registerDialog.showModal();
    break;
}
