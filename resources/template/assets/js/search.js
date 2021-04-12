const $ = window.jQuery;
const { feather } = window;

$(document).ready(() => {
  // Initializes search overlay plugin.
  // Replace onSearchSubmit() and onKeyEnter() with
  // your logic to perform a search and display results
  $('[data-pages="search"]').search({
    searchField: '#overlay-search',
    closeButton: '.overlay-close',
    suggestions: '#overlay-suggestions',
    brand: '.brand',
    onSearchSubmit(searchString) {
      console.log(`Search for: ${searchString}`);
    },
    onKeyEnter(searchString) {
      console.log(`Live search for: ${searchString}`);
      const searchField = $('#overlay-search');
      const searchResults = $('.search-results');
      clearTimeout($.data(this, 'timer'));
      searchResults.fadeOut('fast');
      const wait = setTimeout(() => {
        searchResults.find('.result-name').each(function () {
          if (searchField.val().length != 0) {
            $(this).html(searchField.val());
            searchResults.fadeIn('fast');
          }
        });
      }, 500);
      $(this).data('timer', wait);
    },
  });

  // https://github.com/colebemis/feather
  // Feather ICONS
  // Used in sidebar icons

  feather.replace({
    width: 16,
    height: 16,
  });
});
