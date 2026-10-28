import Swal from 'sweetalert2';

export const confirmAction = ({
    title,
    text,
    confirmButtonText = 'Continue',
    icon = 'warning',
}) => Swal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#176b87',
    cancelButtonColor: '#6c757d',
    reverseButtons: true,
    focusCancel: true,
}).then((result) => result.isConfirmed);

export const showToast = (title, icon = 'success') => Swal.fire({
    toast: true,
    position: 'top-end',
    icon,
    title,
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
});
