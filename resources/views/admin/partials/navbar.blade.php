<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center position-relative">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input
                    type="text"
                    class="form-control border-0 shadow-none"
                    id="dashboard-global-search"
                    placeholder="Search dashboard cards... "
                    aria-label="Search..."
                    autocomplete="off"
                />
                <div class="search-results dropdown-menu" id="global-search-results" style="display: none; width: 400px; max-height: 400px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <!-- Search results will be populated here -->
                    </div>
                </div>
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- User Dropdown -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    {{-- <div class="avatar avatar-online">
                        <img src="{{ auth()->user()->image ? asset('storage/profile/' . auth()->user()->image) : asset('api/assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle" />
                    </div> --}}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ ucfirst(Auth::user()->name ?? 'Jhone Doe') }}</h6>
                                    <small class="text-muted">{{ Auth()->user()->role->name ?? 'Admin' }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.profile') }}">
                            <i class="bx bx-user bx-md me-3"></i><span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"> <i class="bx bx-cog bx-md me-3"></i><span>Settings</span> </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="mdi mdi-logout text-muted fs-lg align-middle me-1"></i>
                        <span class="align-middle" data-key="t-logout">Logout</span>
                    </a>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>

<!-- Navbar Search Script Only (Dashboard-specific functions removed) -->
<script>
// Global search functionality - Minimal version
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dashboard-global-search');
    const searchResults = document.getElementById('global-search-results');
    
    if (!searchInput) return;
    
    let searchTimeout;
    let allCardsData = [];
    
    // Check if we're on dashboard page
    function isDashboardPage() {
        return window.location.pathname.includes('dashboard') || 
               document.querySelector('.dashboard-stats-card') !== null;
    }
    
    // Collect card data from dashboard if available
    function collectCardData() {
        if (typeof window.collectDashboardCardData === 'function') {
            allCardsData = window.collectDashboardCardData();
            return allCardsData.length > 0;
        }
        return false;
    }
    
    // Search input event listeners
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const searchTerm = e.target.value.trim();
        
        if (searchTerm.length === 0) {
            hideSearchResults();
            if (typeof window.clearDashboardFilter === 'function') {
                window.clearDashboardFilter();
            }
            return;
        }
        
        if (searchTerm.length < 2) {
            showSearchResults([]);
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performGlobalSearch(searchTerm);
        }, 300);
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            performGlobalSearch(this.value.trim());
        }
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (searchInput && !searchInput.contains(e.target) && 
            searchResults && !searchResults.contains(e.target)) {
            hideSearchResults();
        }
    });
    
    // Perform search
    function performGlobalSearch(searchTerm) {
        // First, try to collect data if not already collected
        if (allCardsData.length === 0) {
            const hasData = collectCardData();
            if (!hasData) {
                showNoDashboardMessage();
                return;
            }
        }
        
        const filteredCards = allCardsData.filter(card => {
            // Check if card is currently hidden by user
            if (window.isCardHidden && window.isCardHidden(card.id)) {
                return false;
            }
            
            // Search in title, category and keywords
            const searchLower = searchTerm.toLowerCase();
            return card.title.toLowerCase().includes(searchLower) ||
                   card.category.toLowerCase().includes(searchLower) ||
                   (card.keywords && card.keywords.some(keyword => 
                       keyword.toLowerCase().includes(searchLower)
                   ));
        });
        
        showSearchResults(filteredCards, searchTerm);
        
        // Apply filter on dashboard if function exists
        if (typeof window.filterDashboardCards === 'function') {
            window.filterDashboardCards(searchTerm);
        }
    }
    
    // Show search results in dropdown
    function showSearchResults(results, searchTerm = '') {
        const resultsContainer = searchResults.querySelector('.list-group');
        resultsContainer.innerHTML = '';
        
        if (results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="no-results p-3 text-center">
                    <i class="bx bx-search-alt bx-md mb-2 text-muted"></i>
                    <p class="mb-0">No cards found for "${searchTerm}"</p>
                </div>
            `;
        } else {
            results.forEach((card, index) => {
                const highlightedTitle = highlightText(card.title, searchTerm);
                const item = document.createElement('a');
                item.className = `list-group-item list-group-item-action search-result-item ${index === 0 ? 'active' : ''}`;
                item.href = '#';
                item.dataset.cardId = card.id;
                item.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm">
                                <span class="avatar-initial rounded bg-label-${card.color || 'primary'}">
                                    <i class="${card.icon}"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="search-result-title mb-1">${highlightedTitle}</div>
                            <div class="search-result-category small">${card.category}</div>
                        </div>
                    </div>
                `;
                
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cardId = this.dataset.cardId;
                    
                    // If we're on dashboard page, scroll to card
                    if (window.scrollToDashboardCard) {
                        window.scrollToDashboardCard(cardId);
                    } else {
                        // If not on dashboard, redirect to dashboard with search
                        window.location.href = '{{ route("admin.dashboard") }}?search=' + encodeURIComponent(searchTerm);
                    }
                    
                    searchInput.value = '';
                    hideSearchResults();
                    
                    // Clear filter
                    if (typeof window.clearDashboardFilter === 'function') {
                        window.clearDashboardFilter();
                    }
                });
                
                item.addEventListener('mouseenter', function() {
                    resultsContainer.querySelectorAll('.search-result-item').forEach(el => {
                        el.classList.remove('active');
                    });
                    this.classList.add('active');
                });
                
                resultsContainer.appendChild(item);
            });
        }
        
        searchResults.style.display = 'block';
        positionSearchResults();
    }
    
    // Show message when not on dashboard or no data
    function showNoDashboardMessage() {
        const resultsContainer = searchResults.querySelector('.list-group');
        resultsContainer.innerHTML = `
            <div class="no-results p-3 text-center">
                <i class="bx bx-info-circle bx-md mb-2 text-muted"></i>
                <p class="mb-2">Search is only available on dashboard page</p>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-primary">Go to Dashboard</a>
            </div>
        `;
        searchResults.style.display = 'block';
        positionSearchResults();
    }
    
    // Position search results dropdown
    function positionSearchResults() {
        if (searchInput && searchResults) {
            const inputRect = searchInput.getBoundingClientRect();
            searchResults.style.position = 'fixed';
            searchResults.style.left = inputRect.left + 'px';
            searchResults.style.top = (inputRect.bottom + window.scrollY) + 'px';
            searchResults.style.width = '400px';
        }
    }
    
    // Hide search results
    function hideSearchResults() {
        if (searchResults) {
            searchResults.style.display = 'none';
        }
    }
    
    // Highlight matching text
    function highlightText(text, searchTerm) {
        if (!searchTerm || !text) return text;
        
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<span class="search-highlight">$1</span>');
    }
    
    // Keyboard navigation for search results
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideSearchResults();
            if (typeof window.clearDashboardFilter === 'function') {
                window.clearDashboardFilter();
            }
            this.value = '';
            this.blur();
            return;
        }
        
        if (!searchResults || searchResults.style.display === 'none') {
            return;
        }
        
        const items = searchResults.querySelectorAll('.search-result-item');
        if (items.length === 0) return;
        
        let activeIndex = -1;
        items.forEach((item, index) => {
            if (item.classList.contains('active')) {
                activeIndex = index;
            }
        });
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (activeIndex < items.length - 1) {
                items[activeIndex]?.classList.remove('active');
                items[activeIndex + 1].classList.add('active');
                items[activeIndex + 1].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (activeIndex > 0) {
                items[activeIndex]?.classList.remove('active');
                items[activeIndex - 1].classList.add('active');
                items[activeIndex - 1].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const activeItem = searchResults.querySelector('.search-result-item.active');
            if (activeItem) {
                activeItem.click();
            }
        }
    });
    
    // Keyboard shortcut Ctrl+K to focus search
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
    
    // Reposition search results on window resize
    window.addEventListener('resize', function() {
        if (searchResults && searchResults.style.display === 'block') {
            positionSearchResults();
        }
    });
});
</script>

<style>
/* Global Search Styles for Navbar */
.search-results {
    z-index: 9999 !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
    margin-top: 5px;
    max-width: 400px;
}

.search-result-item {
    border: none;
    border-bottom: 1px solid #f0f0f0;
    padding: 10px 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-result-item:hover {
    background-color: #f8f9fa;
}

.search-result-item.active {
    background-color: #e7f1ff;
}

.search-result-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.search-result-category {
    font-size: 0.8rem;
    color: #6c757d;
}

.search-highlight {
    background-color: #fff3cd;
    padding: 1px 3px;
    border-radius: 3px;
    font-weight: 600;
}

.no-results {
    color: #6c757d;
}

.avatar-initial {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>