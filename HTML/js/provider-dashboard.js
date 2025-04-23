// Basic functionality for provider dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log("Provider dashboard initialized");
    setupUIComponents();
    setupDebugTools();
    fetchClients();
    
    // Setup search functionality
    const searchInput = document.getElementById('client-search');
    if (searchInput) {
        searchInput.addEventListener('input', filterClients);
    }
    
    // Setup sort functionality
    const sortSelect = document.getElementById('sort-by');
    if (sortSelect) {
        sortSelect.addEventListener('change', sortClients);
    }
    
    // Setup refresh button
    const refreshButton = document.getElementById('refresh-clients');
    if (refreshButton) {
        refreshButton.addEventListener('click', fetchClients);
    }
});

/**
 * Setup UI components and event listeners
 */
function setupUIComponents() {
    // Setup profile dropdown
    const profileToggle = document.getElementById('profile-dropdown-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', function() {
            profileDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking elsewhere
        document.addEventListener('click', function(event) {
            if (!profileToggle.contains(event.target) && !profileDropdown.contains(event.target)) {
                profileDropdown.classList.add('hidden');
            }
        });
    }
    
    // Setup mobile menu
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // Setup logout button
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            // Clear storage
            localStorage.clear();
            sessionStorage.clear();
            
            // Redirect to login
            window.location.href = 'login.html';
        });
    }
}

/**
 * Setup debugging tools to help troubleshoot authentication issues
 */
function setupDebugTools() {
    // Set provider type button
    const setProviderTypeBtn = document.getElementById('set-provider-type');
    if (setProviderTypeBtn) {
        setProviderTypeBtn.addEventListener('click', function() {
            // Set provider type in both localStorage and sessionStorage
            localStorage.setItem('userType', 'provider');
            sessionStorage.setItem('userType', 'provider');
            
            // Create a dummy session if needed
            if (!localStorage.getItem('session_id') && !sessionStorage.getItem('session_id')) {
                const dummySession = 'debug_session_' + Date.now();
                localStorage.setItem('session_id', dummySession);
                sessionStorage.setItem('session_id', dummySession);
            }
            
            // Display success message
            alert('Provider type set! Reloading page...');
            
            // Reload page
            window.location.reload();
        });
    }
    
    // Clear storage button
    const clearStorageBtn = document.getElementById('clear-storage');
    if (clearStorageBtn) {
        clearStorageBtn.addEventListener('click', function() {
            // Clear all data from localStorage and sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            // Clear cookies
            document.cookie.split(";").forEach(function(c) {
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
            });
            
            // Display success message
            alert('All storage cleared! Reloading page...');
            
            // Reload page
            window.location.reload();
        });
    }
    
    // Test database connection button
    const testDbBtn = document.getElementById('test-db-connection');
    const dbResultContainer = document.getElementById('db-connection-result');
    
    if (testDbBtn && dbResultContainer) {
        testDbBtn.addEventListener('click', function() {
            // Show loading state
            testDbBtn.disabled = true;
            testDbBtn.innerHTML = 'Testing...';
            dbResultContainer.innerHTML = 'Checking database connection...';
            dbResultContainer.classList.remove('hidden');
            dbResultContainer.className = dbResultContainer.className.replace(/bg-\w+-\d+/g, 'bg-gray-100');
            
            // Make request to test database connection
            fetch('../backend/check-db.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Database check result:', data);
                    
                    // Format results
                    let resultHTML = '';
                    if (data.success) {
                        dbResultContainer.className = dbResultContainer.className.replace(/bg-\w+-\d+/g, 'bg-green-100');
                        resultHTML += '<p class="font-semibold text-green-800">✅ Database connection successful!</p>';
                        
                        if (data.data) {
                            if (data.data.users) {
                                resultHTML += `<p class="mt-2"><strong>Users:</strong> ${data.data.users.total} total (${data.data.users.clients} clients, ${data.data.users.providers} providers)</p>`;
                            }
                            
                            if (data.data.tables) {
                                resultHTML += `<p class="mt-2"><strong>Tables:</strong> ${data.data.tables.join(', ')}</p>`;
                            }
                            
                            if (data.data.connection) {
                                resultHTML += `<p class="mt-2"><strong>MySQL:</strong> ${data.data.connection.server_info}</p>`;
                                resultHTML += `<p><strong>Connection:</strong> ${data.data.connection.host_info}</p>`;
                            }
                        }
                    } else {
                        dbResultContainer.className = dbResultContainer.className.replace(/bg-\w+-\d+/g, 'bg-red-100');
                        resultHTML += `<p class="font-semibold text-red-800">❌ Database connection failed:</p>`;
                        resultHTML += `<p class="text-red-700">${data.message}</p>`;
                        
                        if (data.data) {
                            resultHTML += '<pre class="mt-2 text-xs p-2 bg-gray-800 text-white overflow-auto rounded">' + 
                                           JSON.stringify(data.data, null, 2) + 
                                          '</pre>';
                        }
                        
                        // Add troubleshooting suggestions
                        resultHTML += '<p class="mt-2 text-red-700">Suggestions:</p>';
                        resultHTML += '<ul class="list-disc pl-5 text-red-700">';
                        resultHTML += '<li>Check database configuration in backend/config/database.php</li>';
                        resultHTML += '<li>Verify MySQL server is running</li>';
                        resultHTML += '<li>Check database credentials</li>';
                        resultHTML += '</ul>';
                    }
                    
                    // Show result
                    dbResultContainer.innerHTML = resultHTML;
                })
                .catch(error => {
                    console.error('Error testing database:', error);
                    
                    // Show error
                    dbResultContainer.className = dbResultContainer.className.replace(/bg-\w+-\d+/g, 'bg-red-100');
                    dbResultContainer.innerHTML = `
                        <p class="font-semibold text-red-800">❌ Error testing database connection:</p>
                        <p class="text-red-700">${error.message}</p>
                        <p class="mt-2 text-red-700">Suggestions:</p>
                        <ul class="list-disc pl-5 text-red-700">
                            <li>Check if the PHP file exists at backend/check-db.php</li>
                            <li>Verify PHP is working correctly</li>
                            <li>Check for syntax errors in PHP files</li>
                        </ul>
                    `;
                })
                .finally(() => {
                    // Reset button
                    testDbBtn.disabled = false;
                    testDbBtn.innerHTML = 'Test Database Connection';
                });
        });
    }
}

/**
 * Fetch clients from the backend API
 */
function fetchClients() {
    // Show loading indicator
    const loadingClients = document.getElementById('loading-clients');
    const clientsContainer = document.getElementById('clients-container');
    const noClientsMessage = document.getElementById('no-clients-message');
    
    if (loadingClients) {
        loadingClients.classList.remove('hidden');
    }
    if (clientsContainer) {
        clientsContainer.classList.add('hidden');
    }
    if (noClientsMessage) {
        noClientsMessage.classList.add('hidden');
    }
    
    console.log('Fetching clients from backend...');
    
    // Simple direct fetch without any authentication parameters
    fetch('../backend/fetch-clients.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Clients data:', data);
            
            // Hide loading indicator
            if (loadingClients) {
                loadingClients.classList.add('hidden');
            }
            
            if (data.success) {
                if (data.clients && data.clients.length > 0) {
                    // Display clients
                    displayClients(data.clients);
                    
                    // Update statistics
                    updateClientStatistics(data.clients);
                    
                    // Show clients container
                    if (clientsContainer) {
                        clientsContainer.classList.remove('hidden');
                    }
                } else {
                    // Show no clients message
                    if (noClientsMessage) {
                        noClientsMessage.classList.remove('hidden');
                    }
                }
            } else {
                // Show error message
                if (noClientsMessage) {
                    noClientsMessage.classList.remove('hidden');
                    noClientsMessage.querySelector('p').innerHTML = data.message || 'Failed to load clients.';
                }
                console.error('Failed to fetch clients:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching clients:', error);
            
            // Hide loading indicator
            if (loadingClients) {
                loadingClients.classList.add('hidden');
            }
            
            // Show error message
            if (noClientsMessage) {
                noClientsMessage.classList.remove('hidden');
                noClientsMessage.querySelector('p').innerHTML = 'Error loading clients: ' + error.message;
            }
        });
}

/**
 * Display clients using the card-based layout
 * @param {Array} clients - Array of client objects
 */
function displayClients(clients) {
    // Store clients in a global variable for filtering/sorting
    window.allClients = clients;
    
    renderClientCards(clients);
}

/**
 * Render client cards in the grid
 * @param {Array} clients - Array of client objects to display
 */
function renderClientCards(clients) {
    const clientsGrid = document.getElementById('clients-grid');
    if (!clientsGrid) return;
    
    // Clear existing cards
    clientsGrid.innerHTML = '';
    
    if (clients.length === 0) {
        // Show no results message
        const noResults = document.createElement('div');
        noResults.className = 'col-span-full text-center py-8';
        noResults.innerHTML = `
            <p class="text-gray-500">No clients match your search criteria.</p>
        `;
        clientsGrid.appendChild(noResults);
        return;
    }
    
    // Add client cards
    clients.forEach(client => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-100';
        card.setAttribute('data-client-id', client.id);
        
        // Generate random background color (light) for the card header
        const colors = ['bg-blue-100', 'bg-green-100', 'bg-purple-100', 'bg-pink-100', 'bg-yellow-100', 'bg-indigo-100'];
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        
        // Generate initials for the avatar
        const nameParts = (client.full_name || '').split(' ');
        let initials = '';
        if (nameParts.length > 0) {
            initials = nameParts[0].charAt(0);
            if (nameParts.length > 1) {
                initials += nameParts[nameParts.length - 1].charAt(0);
            }
        }
        initials = initials.toUpperCase();
        
        // Create card content
        card.innerHTML = `
            <div class="${randomColor} px-6 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg ${randomColor.replace('100', '700')} text-white">
                            ${initials}
                        </div>
                        <div class="ml-3">
                            <h3 class="font-semibold text-gray-800">${escapeHtml(client.full_name || 'N/A')}</h3>
                            <p class="text-sm text-gray-600">${escapeHtml(client.formatted_date || 'N/A')}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Email</p>
                    <p class="text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        ${escapeHtml(client.email || 'N/A')}
                    </p>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Phone</p>
                    <p class="text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        ${escapeHtml(client.phone || 'N/A')}
                    </p>
                </div>
                <div class="mt-6 flex justify-end">
                    <button class="view-client-btn bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors duration-200 px-4 py-2 rounded-lg text-sm font-medium" 
                            data-client-id="${client.id}">
                        View Details
                    </button>
                </div>
            </div>
        `;
        
        // Add to grid
        clientsGrid.appendChild(card);
        
        // Add event listener to view button
        const viewBtn = card.querySelector('.view-client-btn');
        if (viewBtn) {
            viewBtn.addEventListener('click', function() {
                const clientId = this.getAttribute('data-client-id');
                viewClientDetails(clientId);
            });
        }
    });
}

/**
 * Filter clients based on search input
 */
function filterClients() {
    const searchInput = document.getElementById('client-search');
    if (!searchInput || !window.allClients) return;
    
    const searchTerm = searchInput.value.toLowerCase();
    
    // If search term is empty, show all clients
    if (!searchTerm.trim()) {
        renderClientCards(window.allClients);
        return;
    }
    
    // Filter clients based on search term
    const filteredClients = window.allClients.filter(client => {
        const name = (client.full_name || '').toLowerCase();
        const email = (client.email || '').toLowerCase();
        const phone = (client.phone || '').toLowerCase();
        
        return name.includes(searchTerm) || 
               email.includes(searchTerm) || 
               phone.includes(searchTerm);
    });
    
    // Render filtered clients
    renderClientCards(filteredClients);
}

/**
 * Sort clients based on selected option
 */
function sortClients() {
    const sortSelect = document.getElementById('sort-by');
    if (!sortSelect || !window.allClients) return;
    
    const sortBy = sortSelect.value;
    let sortedClients = [...window.allClients];
    
    // Sort clients based on selected option
    if (sortBy === 'name') {
        sortedClients.sort((a, b) => {
            const nameA = (a.full_name || '').toLowerCase();
            const nameB = (b.full_name || '').toLowerCase();
            return nameA.localeCompare(nameB);
        });
    } else if (sortBy === 'date') {
        sortedClients.sort((a, b) => {
            const dateA = new Date(a.created_at || 0);
            const dateB = new Date(b.created_at || 0);
            return dateB - dateA; // Newest first
        });
    }
    
    // Apply any active filters
    const searchInput = document.getElementById('client-search');
    if (searchInput && searchInput.value.trim()) {
        // Re-trigger filter with sorted clients
        filterClients();
        return;
    }
    
    // Render sorted clients
    renderClientCards(sortedClients);
}

/**
 * View client details (placeholder function)
 * @param {string} clientId - The client ID to view
 */
function viewClientDetails(clientId) {
    console.log('Viewing client details for client ID:', clientId);
    alert('Client details functionality will be implemented in a future update.');
    // In a real implementation, this would open a modal or navigate to a client details page
}

/**
 * Helper function to escape HTML to prevent XSS
 * @param {string} unsafe - Unsafe string that might contain HTML
 * @return {string} - Escaped safe string
 */
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return 'N/A';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Update client statistics in the UI
 * @param {Array} clients - Array of client objects
 */
function updateClientStatistics(clients) {
    // Update total clients count
    const totalClientsCount = document.getElementById('total-clients-count');
    if (totalClientsCount) {
        totalClientsCount.textContent = clients.length;
    }
    
    // Calculate new clients this month
    const newClientsCount = document.getElementById('new-clients-count');
    if (newClientsCount) {
        const currentDate = new Date();
        const firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        
        // Count clients registered this month
        const newClients = clients.filter(client => {
            if (!client.created_at) return false;
            const createdDate = new Date(client.created_at);
            return createdDate >= firstDayOfMonth;
        });
        
        newClientsCount.textContent = newClients.length;
    }
    
    // Update animations for numbers
    animateNumbers();
}

/**
 * Animates numbers from 0 to their target value
 */
function animateNumbers() {
    document.querySelectorAll('[id$="-count"]').forEach(element => {
        const target = parseInt(element.textContent);
        if (isNaN(target)) return;
        
        let start = 0;
        const duration = 1000; // ms
        const step = 30; // ms per step
        const increment = target / (duration / step);
        
        element.textContent = '0';
        
        const interval = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = target;
                clearInterval(interval);
            } else {
                element.textContent = Math.floor(start);
            }
        }, step);
    });
} 