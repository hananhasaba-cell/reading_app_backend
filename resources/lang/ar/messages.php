<?php

return [
    //عند تسجيل الدخول وردود التعديل وحذف وتسجيل الخروج من الحساب      
            'phone.regex' => 'رقم الهاتف خطأ، الرجاء كتابة رقم صالح',
            'password.confirmed' => 'كلمة المرور غير مطابقة',
            'birth_date.before' => 'تاريخ الميلاد غير صالح',

            'success_sign_in' => 'تم التسجيل بنجاح',
            'unvalid_informations' => 'بيانات الدخول غير صحيحة',
            'unauthorized' => 'غير مصرح لك بالدخول',
            'incorrect_password' => 'كلمة المرور الحالية غير صحيحة',

            'success_update_account' => 'تم تحديث الحساب بنجاح',
            'success_sign_out' => 'تم تسجل الخروج بنجاح',
            'success_delete_account' => 'تم حذف الحساب بنجاح',

    //  ردود طلبات تغيير الدور من مستأجر لمالك شقة      
            'Error_transfer_landlordTOlandlord' => 'يمكن التحول فقط من مستأجر لمالك شقة',
            'review_request_changeRole' => 'تم إرسال طلب التحول للمراجعة',
            'already_have_pending_request' => 'لديك طلب مقدم بالفعل',

    //ردود طلبات الاشتراك ليتمكن من إضافة شقة       
            'subscription_for_ApartmentOwner_only' => 'طلب الاشتراك متاح لمالكي الشقق فقط',
            'already_have_Active_subscription' => 'لديك اشتراك نشط بالفعل، لا يمكنك إرسال طلب جديد الآن',
            'review_request_subscription' => 'تم إرسال طلب الاشتراك للمراجعة',

            'Read_notifications' => 'تم تعليم جميع الإشعارات كمقروءة',

    //       ردود من عند المدير عند الموافقة على تسجيلات الدخول وطلبات تغيير الدور وطلبات الاشتراك     
            'admin_account_approved' => 'تمت الموافقة على الحساب',
            'admin_account_reject' => 'تم رفض الحساب',
            'admin_listRoleChangeRequests' => 'تم عرض الطلبات بنجاح',
            'admin_listSubscriptionRequests' => 'تم عرض الطلبات بنجاح',

            'roleChange_approved' =>  'تمت الموافقة على التحول إلى مالك شقة',
            'roleChange_reject' =>  'تم رفض طلب التحول',

            'subscription_approved' =>  'تم تفعيل الاشتراك',
            'subscription_reject' =>  'تم رفض طلب الاشتراك',

    //ردود خاصة بحذف شقة وإضافة واحدة جديدة وتعديلها        
            'delete_apartment' => 'تم حذف الشقة بنجاح',
            'AddApartment_for_ApartmentOwner_only' => 'اشتراكك غير مفعل، لا يمكنك إضافة شقق جديدة',
            'add_apartment' => 'تمت إضافة الشقة بنجاح',
            'update_apartment' => 'تم تعديل الشقة بنجاح',
            'updateApartment_for_ApartmentOwner_only' => 'غير مصرح لك بتعديل هذه الشقة',
            'deleteApartment_for_ApartmentOwner_only' => 'غير مصرح لك بحذف هذه الشقة',

    //ردود طلبات الحجز وتعديلات الحجز وإلغاءه وقبول ورفض المؤجر     
            'cancel_booking' => 'تم إلغاء الحجز',
            'already_booked' => 'عذرًا، لا يمكن حجز الشقة في هذه الفترة لأنها محجوزة مسبقًا', 
            'success_creat_booking' =>  'تم إنشاء الحجز بنجاح وهو بانتظار موافقة المؤجر',          
            'responseBooking_for_ApartmentOwner_only' => 'غير مصرح لك بالرد على هذا الحجز',
            'updatebooking_tenant' => 'غير مصرح لك بتعديل هذه الحجز',
            'update&already_booked' =>  'هناك تضارب في التواريخ الجديدة للحجز', 
            'review_request_booking' =>  'تم إرسال طلب تعديل الحجز وهو بانتظار موافقة المؤجر',
            'responseBookingupdate_for_ApartmentOwner_only' => 'غير مصرح لك بالرد على هذا التعديل',
            'booking_update_status' => 'تم تحديث حالة طلب التعديل',
            'add_apartment_favorite' => 'تمت إضافة الشقة إلى المفضلة',
            'already_give_review' => 'لقد قمت بتقييم هذه الشقة مسبقًا ولا يمكنك التقييم مرة أخرى',
            'thereIS_no_booking' => 'لا يوجد حجز لهذه الشقة',
            'cannot_give_review_yet' => 'لا يمكنك تقييم الشقة قبل بدء فترة الحجز',
            'success_review' =>  'تم إضافة التقييم بنجاح',


            'admin_only' => 'غير مصرح لك بالدخول إلى لوحة المدير'
];

?>