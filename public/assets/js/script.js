document.addEventListener("DOMContentLoaded", () => {
    // Dropdown user menu (giữ nguyên phần này nếu đã có)
    const userIcon = document.getElementById("userIcon");
    const dropdown = document.getElementById("userDropdown");

    if (userIcon) {
        userIcon.addEventListener("click", () => {
            userDropdown.style.display =
                userDropdown.style.display === "block" ? "none" : "block";
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

