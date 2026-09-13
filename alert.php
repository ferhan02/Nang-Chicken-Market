<?php
$all_msgs = [
   'success' => $success_msg ?? [],
   'warning' => $warning_msg ?? [],
   'error'   => $error_msg ?? [],
   'info'    => $info_msg ?? [],
];

$alert_titles = [
   'success' => 'Success',
   'warning' => 'Please Check',
   'error'   => 'Something Went Wrong',
   'info'    => 'Notice',
];

foreach ($all_msgs as $type => $messages) {
   foreach ($messages as $msg) {
      echo '<script>Swal.fire({icon:' . json_encode($type)
         . ',title:' . json_encode($alert_titles[$type])
         . ',text:' . json_encode((string)$msg)
         . ',confirmButtonText:"Got it"});</script>';
   }
}
?>

<script>
function confirm(e, message) {
   e.preventDefault();

   const form = e.currentTarget.form;
   const button = e.currentTarget;

   Swal.fire({
      title: message,
      text: 'Please confirm that you want to continue.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Continue',
      cancelButtonText: 'Cancel'
   }).then((result) => {
      if (result.isConfirmed && form) form.requestSubmit(button);
   });

   return false;
}
</script>
