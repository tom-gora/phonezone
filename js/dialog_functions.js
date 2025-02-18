// functions
export const clearEditForm = (editForm) => {
  editForm
    .querySelector("input[type='hidden']")
    .setAttribute("name", "edit-user-id");
  editForm.querySelector("input[name='edit-user-id']").value = "";
  editForm.querySelector("#edit-confirmation-box").innerText = "";
  editForm.querySelector("#label-bg").style.backgroundImage = "";
  const emailInput = editForm.querySelector("input[name='edit-user-email']");
  emailInput.value = "";
  emailInput.setAttribute("readonly", "");
  editForm.querySelector("input[name='edit-user-first-name']").value = "";
  editForm.querySelector("input[name='edit-user-last-name']").value = "";
  editForm.querySelector("input[name='edit-user-password']").value = "";
  editForm
    .querySelector("button[type='submit']")
    .querySelector("span[class='relative']").innerText = "Confirm changes";
  const imgLabelText = editForm
    .querySelector("label[for='edit-user-image']")
    .querySelector("h5");
  imgLabelText.innerText = "Change picture";
  imgLabelText.classList.remove("hidden");
  editForm
    .querySelector("label[for='edit-user-image']")
    .querySelector("svg")
    .classList.remove("hidden");
  editForm.querySelector("input[name='edit-user-image']").value = "";
  editForm.querySelector("#label-bg").classList.add("opacity-25");

  editForm.querySelector(
    "select[name='edit-user-type']"
  ).options.selectedIndex = 0;
  editForm.querySelector("h2").innerText = "Edit details";
};

export const prefillFormForEdit = (
  editForm,
  id,
  firstName,
  lastName,
  email,
  img,
  role
) => {
  editForm.querySelector("input[name='edit-user-id']").value = id;
  editForm.querySelector("#edit-confirmation-box").innerHTML =
    `You are editing details of <strong>${firstName} ${lastName}</strong>.`;
  editForm.querySelector("#label-bg").style.backgroundImage =
    `url("../res/user_img/${img}"`;
  editForm.querySelector("input[name='edit-user-email']").value = email;
  editForm.querySelector("input[name='edit-user-first-name']").value =
    firstName;
  editForm.querySelector("input[name='edit-user-last-name']").value = lastName;
  // roleSelect.value = role;
  // nope. had to actually print the select object and manually search where
  // the value of this type of input is set, because simple .value is not a key
  // of dom object of type select. Stack overflow knows shit.
  // console.log(roleSelect);
  const roleSelect = editForm.querySelector("select[name='edit-user-type']");
  switch (role) {
    case "User":
      roleSelect.options.selectedIndex = 0;
      break;
    case "Admin":
      roleSelect.options.selectedIndex = 1;
      break;
    case "Owner":
      roleSelect.options.selectedIndex = 2;
      break;
  }
};

export const resetFormForEdit = (labels, defaultRawHtmlArray) => {
  for (let i = 0; i < labels.length; i++) {
    labels[i].innerHTML = defaultRawHtmlArray[i];
  }
};

export const setFormForAddition = (editForm) => {
  editForm
    .querySelector("input[type='hidden']")
    .setAttribute("name", "add-user");
  const formTypeInput = editForm.querySelector("input[name='edit-user-id']");
  formTypeInput !== null ? (formTypeInput.value = "") : null;
  editForm.querySelector("h2").innerText = "Add a new user.";
  editForm
    .querySelector("label[for='edit-user-image']")
    .querySelector("h5").innerText = "Select picture";
  editForm
    .querySelector("button[type='submit']")
    .querySelector("span[class='relative']").innerText = "Add user";
};

export async function get_role() {
  const data = {
    client_request: "get_role"
  };
  const requestOpts = {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  };
  const url = "../scripts/user_functionality.php";

  return fetch(url, requestOpts)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.text();
    })
    .catch((error) => {
      // Parentheses added here
      console.error("Error:", error);
      return null;
    });
}
