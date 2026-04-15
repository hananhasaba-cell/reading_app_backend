<?php

return [
    //عند تسجيل الدخول وردود التعديل وحذف وتسجيل الخروج من الحساب      
    'phone.regex' => 'Invalid phone number, please enter a valid one',
    'password.confirmed' => 'Password confirmation does not match',
    'birth_date.before' => 'Invalid birth date',

    'success_sign_in' => 'Signed in successfully',
    'unvalid_informations' => 'Login information is incorrect',
    'unauthorized' => 'You are not authorized to access',
    'incorrect_password' => 'The current password is incorrect',

    'success_update_account' => 'Account updated successfully',
    'success_sign_out' => 'Signed out successfully',
    'success_delete_account' => 'Account deleted successfully',

    //  ردود طلبات تغيير الدور من مستأجر لمالك شقة      
    'Error_transfer_landlordTOlandlord' => 'You can only transfer from tenant to apartment owner',
    'review_request_changeRole' => 'Role change request has been submitted for review',
    'already_have_pending_request' => 'you already have a pending request',

    //ردود طلبات الاشتراك ليتمكن من إضافة شقة       
    'subscription_for_ApartmentOwner_only' => 'Subscription request is available only for apartment owners',
    'already_have_Active_subscription' => 'You already have an active subscription, you cannot send a new request now',
    'review_request_subscription' => 'Subscription request has been submitted for review',

    'Read_notifications' => 'All notifications marked as read',

    //       ردود من عند المدير عند الموافقة على تسجيلات الدخول وطلبات تغيير الدور وطلبات الاشتراك     
    'admin_account_approved' => 'Account approved',
    'admin_account_reject' => 'Account rejected',
    'admin_listRoleChangeRequests' => 'Requests displayed successfully',
    'admin_listSubscriptionRequests' => 'Requests displayed successfully',

    'roleChange_approved' => 'Role change to apartment owner approved',
    'roleChange_reject' => 'Role change request rejected',

    'subscription_approved' => 'Subscription activated',
    'subscription_reject' => 'Subscription request rejected',

    //ردود خاصة بحذف شقة وإضافة واحدة جديدة وتعديلها        
    'delete_apartment' => 'Apartment deleted successfully',
    'AddApartment_for_ApartmentOwner_only' => 'Your subscription is not active, you cannot add new apartments',
    'add_apartment' => 'Apartment added successfully',
    'update_apartment' => 'Apartment updated successfully',
    'updateApartment_for_ApartmentOwner_only' => 'You are not authorized to update this apartment',
    'deleteApartment_for_ApartmentOwner_only' => 'You are not authorized to delete this apartment',

     //ردود طلبات الحجز وتعديلات الحجز وإلغاءه وقبول ورفض المؤجر     
    'cancel_booking' => 'Booking cancelled',
    'already_booked' => 'Sorry, the apartment cannot be booked during this period as it is already reserved',
    'success_creat_booking' => 'Booking created successfully and is awaiting landlord approval',
    'responseBooking_for_ApartmentOwner_only' => 'You are not authorized to respond to this booking',
    'updatebooking_tenant' => 'You are not authorized to update this booking',
    'update&already_booked' => 'There is a conflict with the new booking dates',
    'review_request_booking' => 'Booking modification request submitted and is awaiting landlord approval',
    'responseBookingupdate_for_ApartmentOwner_only' => 'You are not authorized to respond to this modification',
    'booking_update_status' => 'Booking modification status updated',
    'add_apartment_favorite' => 'Apartment added to favorites',
    'already_give_review' => 'You have already reviewed this apartment and cannot review it again',
    'thereIS_no_booking' => 'No booking exists for this apartment',
    'cannot_give_review_yet' => 'You cannot review the apartment before the booking period starts',
    'success_review' => 'Review added successfully',

    
    'admin_only' => 'You are not authorized to access the admin panel'
];

?>