<?php
/* =========================
   CONFIG & SESSION
========================= */

/*
   Load configuration file.
   Provides database connection as $conn.
*/
require_once __DIR__ . '/../config.php';

/*
   Start session to access login data.
*/
session_start();

/* =========================
   MESSAGE STORAGE
========================= */

/*
   Array to store messages.
   These messages are shown using SweetAlert2.
*/
$message = [];

/* =========================
   AUTHENTICATION CHECK
========================= */

/*
   Get logged-in customer ID.
*/
$customer_id = $_SESSION['customer_id'] ?? null;

/*
   Redirect to login page if user is not logged in.
*/
if (!$customer_id) {
    header('location:login.php');
    exit;
}

/* =========================
   FETCH CUSTOMER DATA
========================= */

/*
   Get customer profile data from database.
*/
$select_profile = $conn->prepare(
    "SELECT customer_name, customer_email, customer_phone, customer_address, customer_password, customer_image
     FROM customer
     WHERE customer_id = ?"
);
$select_profile->execute([$customer_id]);

/*
   Fetch profile as associative array.
*/
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

/*
   If no profile is found,
   force logout to keep session clean.
*/
if (!$fetch_profile) {
    header('location:logout.php');
    exit;
}

/*
   Store customer data into variables.
   These values are used to prefill the form.
*/
$customer_name    = $fetch_profile['customer_name'] ?? '';
$customer_email   = $fetch_profile['customer_email'] ?? '';
$customer_phone   = $fetch_profile['customer_phone'] ?? '';
$customer_address = $fetch_profile['customer_address'] ?? '';
$customer_image   = $fetch_profile['customer_image'] ?? '';

/*
   Only display the image belonging to this logged-in customer.
*/
if (
    $customer_image !== '' &&
    file_exists(__DIR__ . '/../images/' . basename($customer_image))
) {
    $profile_image_src = '../images/' . rawurlencode(basename($customer_image));
} else {
    $profile_image_src = '../images/default-user.jpg';
}

/* =========================
   HANDLE PROFILE UPDATE
========================= */

/*
   Run when user clicks "Update Profile".
*/
if (isset($_POST['update_profile'])) {

    /*
       Read new values from form.
    */
    $new_name    = trim($_POST['customer_name'] ?? '');
    $new_email   = trim($_POST['customer_email'] ?? '');
    $new_phone   = trim($_POST['customer_phone'] ?? '');
    $new_address = trim($_POST['customer_address'] ?? '');
    $cropped_profile_image = trim($_POST['cropped_profile_image'] ?? '');

    /*
       Basic validation rules.
    */
    if ($new_name === '' || $new_email === '' || $new_phone === '' || $new_address === '') {

        $message[] = [
            'type' => 'error',
            'text' => 'All fields are required.'
        ];

    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {

        $message[] = [
            'type' => 'error',
            'text' => 'Please enter a valid email address.'
        ];

    } elseif (!preg_match('/^\d+$/', $new_phone)) {

        $message[] = [
            'type' => 'error',
            'text' => 'Phone number must contain digits only.'
        ];

    } else {

        /*
        Keep the customer's existing picture unless
        a new cropped image was supplied.
        */
        $new_customer_image = $customer_image;
        $new_image_path = null;
        $old_image_to_delete = null;

        if ($cropped_profile_image !== '') {

            /*
            Cropper sends the finished image as JPEG data.
            */
            $prefix = 'data:image/jpeg;base64,';

            if (strpos($cropped_profile_image, $prefix) !== 0) {

                $message[] = [
                    'type' => 'error',
                    'text' => 'Invalid profile image.'
                ];

            } else {

                $base64_data = substr(
                    $cropped_profile_image,
                    strlen($prefix)
                );

                $image_data = base64_decode($base64_data, true);

                /*
                Maximum final cropped image size: 5 MB.
                */
                if (
                    $image_data === false ||
                    strlen($image_data) > 5 * 1024 * 1024
                ) {

                    $message[] = [
                        'type' => 'error',
                        'text' => 'Profile image is too large or invalid.'
                    ];

                } else {

                    /*
                    Confirm that the decoded data is actually an image.
                    */
                    $image_info = @getimagesizefromstring($image_data);

                    if (
                        $image_info === false ||
                        ($image_info['mime'] ?? '') !== 'image/jpeg'
                    ) {

                        $message[] = [
                            'type' => 'error',
                            'text' => 'Invalid profile image format.'
                        ];

                    } else {

                        /*
                        Generate a unique filename tied to this customer.
                        */
                        /*
                        Convert username into a safe filename.
                        Example:
                        "Muhammad Ferhan" -> "muhammad_ferhan_pfp.jpg"
                        */
                        $safe_username = strtolower($new_name);

                        $safe_username = preg_replace(
                            '/[^a-z0-9_-]+/',
                            '_',
                            $safe_username
                        );

                        $safe_username = trim($safe_username, '_-');

                        if ($safe_username === '') {
                            $safe_username = 'customer_' . (int)$customer_id;
                        }

                        $new_customer_image = $safe_username . '_pfp.jpg';

                        $new_image_path =
                            __DIR__ .
                            '/../images/' .
                            $new_customer_image;

                        /*
                        Save cropped picture to /images.
                        */
                        if (file_put_contents($new_image_path, $image_data) === false) {

                            $message[] = [
                                'type' => 'error',
                                'text' => 'Unable to save profile picture.'
                            ];

                            $new_customer_image = $customer_image;
                            $new_image_path = null;

                        } else {

                            /*
                            Remember the old picture so it can be deleted
                            only after the database successfully updates.
                            */
                            if (
                                $customer_image !== '' &&
                                strpos(
                                    basename($customer_image),
                                    'profile_' . (int)$customer_id . '_'
                                ) === 0
                            ) {
                                $old_image_to_delete =
                                    __DIR__ .
                                    '/../images/' .
                                    basename($customer_image);
                            }
                        }
                    }
                }
            }
        }

        /* =========================
           UPDATE DATABASE
        ========================= */

        /*
           Update customer profile in database.
        */
        $update = $conn->prepare("
            UPDATE customer
            SET customer_name = ?,
                customer_email = ?,
                customer_phone = ?,
                customer_address = ?,
                customer_image = ?
            WHERE customer_id = ?
        ");

        if ($update->execute([
            $new_name,
            $new_email,
            $new_phone,
            $new_address,
            $new_customer_image,
            $customer_id
        ])) {

            /*
               Success message.
            */
            $message[] = [
                'type' => 'success',
                'text' => 'Profile updated successfully.'
            ];

            /*
               Update displayed values immediately
               without reloading from database.
            */
            $customer_name    = $new_name;
            $customer_email   = $new_email;
            $customer_phone   = $new_phone;
            $customer_address = $new_address;
            $customer_image = $new_customer_image;

        /*
        Remove the customer's previous profile picture
        after the new one was successfully saved.
        */
        if (
            $old_image_to_delete &&
            is_file($old_image_to_delete) &&
            $old_image_to_delete !== $new_image_path
        ) {
            @unlink($old_image_to_delete);
        }

        /*
        Immediately update the displayed image.
        */
        if (
            $customer_image !== '' &&
            file_exists(__DIR__ . '/../images/' . basename($customer_image))
        ) {
            $profile_image_src =
                '../images/' .
                rawurlencode(basename($customer_image));
        } else {
            $profile_image_src = '../images/default-user.jpg';
        }

        } else {

            /*
            Database failed, so remove the newly-created image
            instead of leaving an unused file behind.
            */
            if ($new_image_path && is_file($new_image_path)) {
                @unlink($new_image_path);
            }

            /*
               Database update failed error message.
            */
            $message[] = [
                'type' => 'error',
                'text' => 'Failed to update profile. Please try again.'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<!-- Page title -->
<title>My Profile</title>

<!-- Responsive layout -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<!-- Global styles -->

<!-- Profile page styles -->

<!-- SweetAlert for popup messages -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">

<!-- Cropper.js -->
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css"
>

<script
    src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js">
</script>

</head>
<body>

<?php include 'header.php'; ?>

<!-- =========================
     PROFILE PAGE
========================= -->
<section class="profile-page">

    <div class="page-heading">
        <a href="home.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>
        <span class="eyebrow">Account settings</span>
    </div>

    <div class="profile-card">

        <!-- =========================
             PROFILE HEADER
        ========================= -->
        <div class="profile-header">
            <div class="profile-picture-section">

                <div class="profile-picture-preview">
                    <img
                        src="<?= htmlspecialchars($profile_image_src); ?>"
                        alt="My Profile Picture"
                        class="profile-image"
                        id="profileImagePreview"
                    >

                    <label
                        for="profileImageInput"
                        class="profile-picture-edit"
                        title="Change profile picture"
                    >
                        <i class="fa-solid fa-camera"></i>
                    </label>
                </div>

                <input
                    type="file"
                    id="profileImageInput"
                    accept="image/jpeg,image/png,image/webp"
                    hidden
                >

                <button
                    type="button"
                    class="profile-photo-button"
                    id="chooseProfilePhoto"
                >
                    <i class="fa-solid fa-image"></i>
                    Change Photo
                </button>

            </div>

            <div class="profile-header-text">
                <h1>My Profile</h1>
                <p class="profile-sub">
                    Manage your personal details and keep your account info up to date.
                </p>
            </div>
        </div>

        <!-- =========================
             PROFILE FORM
        ========================= -->
        <form method="POST" id="profileForm" class="profile-form">

            <div class="profile-grid">

                <!-- Username -->
                <div class="field">
                    <label for="customer_name">Username</label>
                    <input
                        type="text"
                        name="customer_name"
                        id="customer_name"
                        value="<?= htmlspecialchars($customer_name); ?>"
                        required
                    >
                </div>

                <!-- Email -->
                <div class="field">
                    <label for="customer_email">Email Address</label>
                    <input
                        type="email"
                        name="customer_email"
                        id="customer_email"
                        value="<?= htmlspecialchars($customer_email); ?>"
                        required
                    >
                </div>

                <!-- Phone -->
                <div class="field">
                    <label for="customer_phone">Phone Number</label>
                    <input
                        type="text"
                        name="customer_phone"
                        id="customer_phone"
                        value="<?= htmlspecialchars($customer_phone); ?>"
                        required
                    >
                </div>

                <!-- Address -->
                <div class="field field-full">
                    <label for="customer_address">Address</label>
                    <input
                        type="text"
                        name="customer_address"
                        id="customer_address"
                        value="<?= htmlspecialchars($customer_address); ?>"
                        required
                    >
                </div>

                <!-- Password placeholder -->
                <div class="field">
                    <label>Password</label>
                    <input type="password" value="************" disabled>
                    <small class="hint">
                        For security reasons, your password is hidden.
                    </small>
                </div>

            </div>

            <!-- =========================
                 PROFILE ACTIONS
            ========================= -->
            <div class="profile-actions">

                <!-- Update profile button -->
                <button
                    type="button"
                    class="btn update-btn"
                    onclick="confirmUpdate()">
                    Update Profile
                </button>

                <!-- Change password link -->
                <a href="change_password.php" class="option-btn change-pass-btn">
                    Change Password
                </a>

                <input
                    type="hidden"
                    name="cropped_profile_image"
                    id="croppedProfileImage"
                    value=""
                >

                <!-- Hidden input used to detect update -->
                <input type="hidden" name="update_profile" value="1">
            </div>

        </form>
    </div>
</section>

<!-- =========================
     PROFILE IMAGE CROPPER
========================= -->

<div class="crop-modal" id="cropModal" aria-hidden="true">

    <div class="crop-modal-card">

        <div class="crop-modal-header">
            <div>
                <h2>Adjust Profile Picture</h2>
                <p>Drag and zoom the image until it fits comfortably.</p>
            </div>

            <button
                type="button"
                class="crop-close"
                id="closeCropModal"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="crop-workspace">
            <img
                id="cropImage"
                src=""
                alt="Profile picture crop preview"
            >
        </div>

        <div class="crop-controls">

            <button
                type="button"
                class="crop-control-btn"
                id="zoomOutProfile"
            >
                <i class="fa-solid fa-minus"></i>
                Zoom Out
            </button>

            <button
                type="button"
                class="crop-control-btn"
                id="zoomInProfile"
            >
                <i class="fa-solid fa-plus"></i>
                Zoom In
            </button>

            <button
                type="button"
                class="crop-control-btn"
                id="resetProfileCrop"
            >
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </button>

        </div>

        <div class="crop-modal-actions">

            <button
                type="button"
                class="option-btn"
                id="cancelProfileCrop"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn"
                id="applyProfileCrop"
            >
                <i class="fa-solid fa-check"></i>
                Use Photo
            </button>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>

<script>
/* =========================
   CONFIRM UPDATE (JS)
========================= */

/*
   Show confirmation popup before saving changes.
*/
function confirmUpdate() {

    const form = document.getElementById('profileForm');
    if (!form) return;

    /*
       Front-end check for empty fields.
    */
    const requiredIds = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address'
    ];

    for (const id of requiredIds) {
        const el = document.getElementById(id);
        if (!el || !el.value.trim()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'All fields are required.'
            });
            return;
        }
    }

    /*
       Confirmation popup.
    */
    Swal.fire({
        title: 'Update Profile?',
        text: 'Are you sure you want to save these changes?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, update',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

/* =========================
   DISPLAY PHP MESSAGES
========================= */

<?php if (!empty($message)) : ?>
    <?php foreach ($message as $msg) : ?>
        Swal.fire({
            icon: '<?= $msg['type']; ?>',
            title: '<?= $msg['type'] === 'success' ? 'Success' : 'Error'; ?>',
            text: '<?= $msg['text']; ?>',
            confirmButtonColor: '#3085d6',
            width: '450px',
            padding: '2rem'
        });
    <?php endforeach; ?>
<?php endif; ?>

/* =========================
   PROFILE PICTURE CROPPER
========================= */

const profileImageInput =
    document.getElementById('profileImageInput');

const chooseProfilePhoto =
    document.getElementById('chooseProfilePhoto');

const cropModal =
    document.getElementById('cropModal');

const cropImage =
    document.getElementById('cropImage');

const profileImagePreview =
    document.getElementById('profileImagePreview');

const croppedProfileImage =
    document.getElementById('croppedProfileImage');

const applyProfileCrop =
    document.getElementById('applyProfileCrop');

const cancelProfileCrop =
    document.getElementById('cancelProfileCrop');

const closeCropModal =
    document.getElementById('closeCropModal');

const zoomInProfile =
    document.getElementById('zoomInProfile');

const zoomOutProfile =
    document.getElementById('zoomOutProfile');

const resetProfileCrop =
    document.getElementById('resetProfileCrop');

let profileCropper = null;


/*
   Open normal file chooser.
*/
if (chooseProfilePhoto) {
    chooseProfilePhoto.addEventListener('click', () => {
        profileImageInput.click();
    });
}


/*
   User selected an image.
*/
if (profileImageInput) {

    profileImageInput.addEventListener('change', (event) => {

        const file = event.target.files[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {

            Swal.fire({
                icon: 'error',
                title: 'Unsupported Image',
                text: 'Please choose a JPG, PNG, or WEBP image.'
            });

            profileImageInput.value = '';
            return;
        }

        /*
           Prevent extremely large original files.
           10 MB before cropping.
        */
        if (file.size > 10 * 1024 * 1024) {

            Swal.fire({
                icon: 'error',
                title: 'Image Too Large',
                text: 'Please choose an image smaller than 10 MB.'
            });

            profileImageInput.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {

            cropImage.src = e.target.result;

            cropModal.classList.add('active');
            cropModal.setAttribute('aria-hidden', 'false');

            /*
               Destroy previous Cropper instance.
            */
            if (profileCropper) {
                profileCropper.destroy();
            }

            profileCropper = new Cropper(cropImage, {

                /*
                   Square crop because it will be displayed
                   inside a circular profile picture.
                */
                aspectRatio: 1,

                viewMode: 1,

                dragMode: 'move',

                autoCropArea: 1,

                responsive: true,

                background: false,

                movable: true,

                zoomable: true,

                scalable: false,

                rotatable: false,

                guides: false,

                center: true,

                highlight: false,

                cropBoxMovable: false,

                cropBoxResizable: false,

                toggleDragModeOnDblclick: false
            });
        };

        reader.readAsDataURL(file);
    });
}


/*
   Close crop window.
*/
function closeProfileCropper() {

    cropModal.classList.remove('active');
    cropModal.setAttribute('aria-hidden', 'true');

    if (profileCropper) {
        profileCropper.destroy();
        profileCropper = null;
    }
}


if (cancelProfileCrop) {
    cancelProfileCrop.addEventListener(
        'click',
        closeProfileCropper
    );
}


if (closeCropModal) {
    closeCropModal.addEventListener(
        'click',
        closeProfileCropper
    );
}


/*
   Zoom controls.
*/
if (zoomInProfile) {
    zoomInProfile.addEventListener('click', () => {

        if (profileCropper) {
            profileCropper.zoom(0.1);
        }
    });
}


if (zoomOutProfile) {
    zoomOutProfile.addEventListener('click', () => {

        if (profileCropper) {
            profileCropper.zoom(-0.1);
        }
    });
}


if (resetProfileCrop) {
    resetProfileCrop.addEventListener('click', () => {

        if (profileCropper) {
            profileCropper.reset();
        }
    });
}


/*
   Finish cropping.
*/
if (applyProfileCrop) {

    applyProfileCrop.addEventListener('click', () => {

        if (!profileCropper) {
            return;
        }

        /*
           Produce a consistent 600 × 600 profile picture.
        */
        const canvas = profileCropper.getCroppedCanvas({
            width: 600,
            height: 600,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
            fillColor: '#ffffff'
        });

        /*
           Convert result to JPEG.
        */
        const croppedData =
            canvas.toDataURL('image/jpeg', 0.9);

        /*
           Store it for PHP.
        */
        croppedProfileImage.value = croppedData;

        /*
           Immediately show the cropped result
           in the circular profile preview.
        */
        profileImagePreview.src = croppedData;

        closeProfileCropper();

        Swal.fire({
            icon: 'success',
            title: 'Photo Ready',
            text: 'Click Update Profile to save your new picture.',
            timer: 1800,
            showConfirmButton: false
        });
    });
}


/*
   Escape key closes crop window.
*/
document.addEventListener('keydown', (event) => {

    if (
        event.key === 'Escape' &&
        cropModal.classList.contains('active')
    ) {
        closeProfileCropper();
    }
});

</script>

</body>
</html>
