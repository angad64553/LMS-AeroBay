document.addEventListener("DOMContentLoaded", function () {

    console.log("REPORT JS RUNNING");

    //  extra safety (agar kisi reason se global load ho jaye)
    if (!window.location.href.includes("viewreport.php")) return;

    let container = document.createElement("div");
    container.style.margin = "15px 0";

    // 🔹 SELECT ALL
    let selectBtn = document.createElement("button");
    selectBtn.type = "button";
    selectBtn.innerText = "Select All";
    selectBtn.onclick = () => {
        document.querySelectorAll('.usercheck').forEach(cb => cb.checked = true);
    };

    // 🔹 UNSELECT ALL
    let unselectBtn = document.createElement("button");
    unselectBtn.type = "button";
    unselectBtn.innerText = "Unselect All";
    unselectBtn.style.marginLeft = "10px";
    unselectBtn.onclick = () => {
        document.querySelectorAll('.usercheck').forEach(cb => cb.checked = false);
    };

    // 🔹 SEND NOTIFICATION
    let sendBtn = document.createElement("button");
    sendBtn.type = "button";
    sendBtn.innerText = "Send Notification";
    sendBtn.style.marginLeft = "10px";
    sendBtn.style.background = "#28a745";
    sendBtn.style.color = "#fff";

    sendBtn.onclick = function (e) {
        e.preventDefault();

        let ids = Array.from(document.querySelectorAll('.usercheck:checked'))
            .map(cb => cb.value);

        if (ids.length === 0) {
            alert("Please select at least one student");
            return;
        }

        let courseid = new URLSearchParams(window.location.search).get("courseid");

        //  correct absolute path (important)
        let url = window.location.origin +
            "/moodle/blocks/configurable_reports/send_notification.php";

        window.location.href = url +
            "?userids=" + ids.join(",") +
            "&courseid=" + courseid;
    };

    // 🔹 ADD BUTTONS
    container.appendChild(selectBtn);
    container.appendChild(unselectBtn);
    container.appendChild(sendBtn);

    // 🔹 INSERT ABOVE TABLE
    let table = document.querySelector("table");
    if (table) {
        table.parentNode.insertBefore(container, table);
    }

});