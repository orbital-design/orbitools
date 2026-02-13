/**
 * Query Loop Block Frontend
 *
 * Handles load more pagination via REST API.
 *
 * @file blocks/query-loop/frontend.js
 * @since 1.0.0
 */

(function () {
	'use strict';

	function initLoadMore() {
		const containers = document.querySelectorAll(
			'.orb-query-loop__load-more'
		);

		containers.forEach(function (container) {
			const btn = container.querySelector(
				'.orb-query-loop__load-more-btn'
			);
			if (!btn || btn.dataset.initialized) {
				return;
			}
			btn.dataset.initialized = 'true';

			let currentPage = parseInt(container.dataset.page, 10) || 1;
			const maxPages =
				parseInt(container.dataset.maxPages, 10) || 1;
			const restUrl = container.dataset.restUrl;
			const nonce = container.dataset.nonce;

			let queryParams;
			try {
				queryParams = JSON.parse(container.dataset.queryParams);
			} catch (e) {
				return;
			}

			const resultsContainer =
				container.parentElement.querySelector(
					'.orb-query-loop__results'
				);
			if (!resultsContainer) {
				return;
			}

			if (currentPage >= maxPages) {
				container.style.display = 'none';
			}

			btn.addEventListener('click', function () {
				if (btn.disabled) {
					return;
				}

				const nextPage = currentPage + 1;
				btn.disabled = true;
				btn.classList.add('is-loading');

				fetch(restUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({
						query_parameters: queryParams,
						page: nextPage,
					}),
				})
					.then(function (response) {
						return response.json();
					})
					.then(function (data) {
						if (data.html) {
							resultsContainer.insertAdjacentHTML(
								'beforeend',
								data.html
							);
							currentPage = nextPage;
						}

						if (!data.has_more) {
							container.style.display = 'none';
						}
					})
					.catch(function () {
						// Silently fail - button remains clickable
					})
					.finally(function () {
						btn.disabled = false;
						btn.classList.remove('is-loading');
					});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initLoadMore);
	} else {
		initLoadMore();
	}
})();
