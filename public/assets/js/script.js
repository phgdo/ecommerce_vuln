document.addEventListener("DOMContentLoaded", () => {
    // Dropdown user menu (giữ nguyên phần này nếu đã có)
    const userIcon = document.getElementById("userIcon");
    const dropdown = document.getElementById("userDropdown");

    if (userIcon && dropdown) {
        userIcon.addEventListener("click", () => {
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", (e) => {
            if (!userIcon.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });
    }

    // Tự động đổi ảnh banner
    const bannerImg = document.getElementById("bannerImage");
    const totalImages = 5;
    let current = 1;

    setInterval(() => {
        current = current % totalImages + 1; // chuyển từ 1 → 5
        bannerImg.style.opacity = 0;
        setTimeout(() => {
            bannerImg.src = `assets/resources/banner${current}.jpg`;
            bannerImg.style.opacity = 1;
        }, 500);
    }, 4000); // đổi ảnh mỗi 4 giây
});

