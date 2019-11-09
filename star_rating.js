$(function () {

  var
    processURL = '/process_star_rating.php',
    output = [],
    ratingStarClass = '.star-rating_active .star-rating__item';
  if (localStorage.getItem('star_rating')) {
    output = JSON.parse(localStorage.getItem('star_rating'));
  }
  $('.star-rating').each(function () {
    var
      _this = this,
      ratingId = $(_this).attr('data-id');
    $.post(processURL, { 'action': 'get_rating', 'id': ratingId })
      .done(function (data) {
        if (data['result'] === 'success') {
          var
            ratingAvg = parseFloat(data['data']['rating_avg']),
            totalVotes = data['data']['total_votes'];
          $(_this).find('.star-rating__live').css('width', ratingAvg.toFixed(1) / 5 * 100 + '%');
          $(_this).closest('.star-rating__wrapper').find('.star-rating__avg').text(ratingAvg.toFixed(1));
          $(_this).closest('.star-rating__wrapper').find('.star-rating__votes').text('оценок: ' + totalVotes);
          if (data['data']['is_vote'] !== undefined) {
            if (data['data']['is_vote'] === false) {
              if (output.indexOf(ratingId) < 0) {
                $(_this).addClass('star-rating_active');
              }
            }
          } else {
            if (output.indexOf(ratingId) < 0) {
              $(_this).addClass('star-rating_active');
            }
          }
        }
      });
  });

  var starRatingItems = $('.star-rating__live .star-rating__item');
  starRatingItems.on('mouseover', function () {
    var
      rating = $(this).attr('data-rating'),
      items = $(this).closest('.star-rating__live').find('.star-rating__item');
    if (!$(this).closest('.star-rating').hasClass('star-rating_active')) {
      return;
    }
    items.each(function (index, element) {
      if (index < rating) {
        if (!$(element).hasClass('star-rating__item_active')) {
          $(element).addClass('star-rating__item_active');
        } else {
          if ($(element).hasClass('star-rating__item_active')) {
            $(element).removeClass('star-rating__item_active');
          }
        }
      }
    })
  });

  starRatingItems.on('mouseout', function () {
    if (!$(this).closest('.star-rating').hasClass('star-rating_active')) {
      return;
    }
    $(this).closest('.star-rating__live').find('.star-rating__item').removeClass('star-rating__item_active');
  });

  $(document).on('click', ratingStarClass, function (e) {
    e.preventDefault();
    var
      _this = this,
      ratingId = $(_this).closest('.star-rating').attr('data-id'),
      rating = $(_this).attr('data-rating');
    $.post(processURL, { 'action': 'set_rating', 'id': ratingId, 'rating': rating })
      .done(function (data) {
        if (!$.isEmptyObject(data)) {
          if (data['result'] === 'success') {
            var
              ratingAvg = parseFloat(data['data']['rating_avg']),
              totalVotes = data['data']['total_votes'],
              output = [];
            $(_this).closest('.star-rating').removeClass('star-rating_active')
              .find('.star-rating__item_active').removeClass('star-rating__item_active')
              .end().find('.star-rating__live').css('width', ratingAvg / 5 * 100 + '%');
            $(_this).closest('.star-rating__wrapper')
              .find('.star-rating__avg').text(ratingAvg.toFixed(1))
              .end().find('.star-rating__votes').text('оценок: ' + totalVotes);
            if (localStorage.getItem('star_rating')) {
              output = JSON.parse(localStorage.getItem('star_rating'));
            }
            if (output.indexOf(ratingId) < 0) {
              output.push(ratingId);
            }
            localStorage.setItem('star_rating', JSON.stringify(output));
          }
        }
      });
  });
});
