'use strict';

function cbxpoll_formsubmit($element, $) {
    var $submit_btn = $element.find('.cbxpoll_vote_btn');
    var wrapper     = $element.closest('.cbxpoll_wrapper');
    var $_this_busy = Number($submit_btn.attr('data-busy'));

    var poll_id    = $submit_btn.attr('data-post-id');
    var reference  = $submit_btn.attr('data-reference');
    var chart_type = $submit_btn.attr('data-charttype');
    var security   = $submit_btn.attr('data-security');

    var user_answer = $element.find('input.cbxpoll_single_answer:checked').serialize();


    if ($_this_busy === 0) {

        $submit_btn.attr('data-busy', '1');
        $submit_btn.prop('disabled', true);

        wrapper.find('.cbvoteajaximage').removeClass('cbvoteajaximagecustom');

        var user_answer_trim = user_answer.trim();

        if (typeof user_answer !== 'undefined' && user_answer_trim.length !== 0) { // if one answer given
            wrapper.find('.cbxpoll-qresponse').hide();

            $.ajax({
                type: 'post',
                dataType: 'json',
                url: cbxpollpublic.ajaxurl,
                data: $element.serialize() + '&user_answer=' + $.base64.btoa(user_answer),
                success: function (data, textStatus, XMLHttpRequest) {
                    if (Number(data.error) === 0) {
                        try { //the data for all graphs
                            if (data.show_result === 1) {
                                wrapper.append(data.html);
                            }

                            wrapper.find('.cbxpoll-qresponse').show();
                            wrapper.find('.cbxpoll-qresponse').removeClass('cbxpoll-qresponse-alert cbxpoll-qresponse-error cbxpoll-qresponse-success');
                            wrapper.find('.cbxpoll-qresponse').addClass('cbxpoll-qresponse-success');
                            wrapper.find('.cbxpoll-qresponse').html('<p>' + data.text + '</p>');

                            wrapper.find('.cbxpoll_answer_wrapper').hide();
                        } catch (e) {

                        }

                    }// end of if not voted
                    else {
                        wrapper.find('.cbxpoll-qresponse').show();
                        wrapper.find('.cbxpoll-qresponse').removeClass('cbxpoll-qresponse-alert cbxpoll-qresponse-error cbxpoll-qresponse-success');
                        wrapper.find('.cbxpoll-qresponse').addClass('cbxpoll-qresponse-error');
                        wrapper.find('.cbxpoll-qresponse').html('<p>' + data.text + '</p>');
                    }

                    $submit_btn.attr('data-busy', '0');
                    $submit_btn.prop('disabled', false);
                    wrapper.find('.cbvoteajaximage').addClass('cbvoteajaximagecustom');
                }//end of success
            })//end of ajax

        }
        else {

            //if no answer given
            $submit_btn.show();
            $submit_btn.attr('data-busy', 0);
            $submit_btn.prop('disabled', false);
            wrapper.find('.cbvoteajaximage').addClass('cbvoteajaximagecustom');

            var error_result = cbxpollpublic.no_answer_error;


            wrapper.find('.cbxpoll-qresponse').show();
            wrapper.find('.cbxpoll-qresponse').removeClass('cbxpoll-qresponse-alert cbxpoll-qresponse-error cbxpoll-qresponse-success');
            wrapper.find('.cbxpoll-qresponse').addClass('cbxpoll-qresponse-alert');
            wrapper.find('.cbxpoll-qresponse').html(error_result);
        }
    }// end of this data busy
}

jQuery(document).ready(function ($) {

    $.base64.utf8encode = true;


    $(document.body).on('click', '.cbxpoll-listing-trig', function (e) {

        e.preventDefault();

        var $this  = $(this);
        var parent = $this.closest('.cbxpoll-listing-wrap');


        var busy     = Number($this.attr('data-busy'));
        var page_no  = Number($this.attr('data-page-no'));
        var per_page = Number($this.attr('data-per-page'));
        var nonce    = $this.attr('data-security');
        var user_id  = Number($this.attr('data-user_id'));

        if (Number(busy) === 0) {
            $this.attr('data-busy', 1);

            $this.find('.cbvoteajaximage').removeClass('cbvoteajaximagecustom');

            $.ajax({

                type: 'post',
                dataType: 'json',
                url: cbxpollpublic.ajaxurl,
                data: {
                    action: 'cbxpoll_list_pagination',
                    page_no: page_no,
                    per_page: per_page,
                    security: nonce,
                    user_id: user_id
                },
                success: function (data, textStatus, XMLHttpRequest) {

                    $this.attr('data-busy', 0);


                    if (data.found) {
                        var content = data.content;
                        parent.find('.cbxpoll-listing').append(content);
                    }


                    //check if we reached at last page
                    var max_num_pages = data.max_num_pages;
                    if ((page_no === max_num_pages) || (data.found === 0)) {
                        $this.parent('.cbxpoll-listing-more').remove();
                    }

                    page_no++;
                    $this.attr('data-page-no', page_no);

                    $this.find('.cbvoteajaximage').addClass('cbvoteajaximagecustom');

                }
            });

        }

    });//end on click


    $(document.body).on('submit', '.cbxpoll-form', function (e) {
        e.preventDefault();

        var $element = $(this);

        let defaultConfig = {
            // class of the parent element where the error/success class is added
            classTo: 'cbxpoll_extra_field_wrap',
            errorClass: 'has-danger',
            successClass: 'has-success',
            // class of the parent element where error text element is appended
            errorTextParent: 'cbxpoll_extra_field_wrap',
            // type of element to create for the error text
            errorTextTag: 'p',
            // class of the error text element
            errorTextClass: 'text-help'
        };

        var pristine = new Pristine($element[0], defaultConfig);
        var valid = pristine.validate(); // returns true or false

        if(!valid) {
            e.preventDefault();
        }
        else{
            cbxpoll_formsubmit($element, $);
        }



    });

    $('.cbxpoll-guest-wrap').on('click', '.cbxpoll-title-login a', function (e) {
        e.preventDefault();

        let $this   = $(this);
        let $parent = $this.closest('.cbxpoll-guest-wrap');
        $parent.find('.cbxpoll-guest-login-wrap').toggle();
    });

});//end dom ready