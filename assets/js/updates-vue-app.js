/**
 * Updates Vue.js Application for Orbital Editor Suite
 * 
 * Provides update management interface with GitHub integration
 * and modern, reactive user experience.
 */

document.addEventListener('DOMContentLoaded', function() {
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                loading: true,
                checking: false,
                updating: false,
                message: '',
                messageType: 'success',
                
                // Update data
                currentVersion: '1.0.0',
                latestVersion: null,
                updateAvailable: false,
                updateInfo: null,
                updateStatus: 'unknown',
                lastChecked: 'Never',
                githubRepo: 'orbital-design/orbital-editor-suite',
                autoUpdates: false,
                
                // Update history
                updateHistory: [],
                
                strings: {}
            };
        },
        
        computed: {
            statusTitle() {
                switch (this.updateStatus) {
                    case 'up-to-date':
                        return 'Plugin is Up to Date';
                    case 'update-available':
                        return 'Update Available';
                    case 'checking':
                        return 'Checking for Updates...';
                    default:
                        return 'Update Status Unknown';
                }
            },
            
            statusMessage() {
                switch (this.updateStatus) {
                    case 'up-to-date':
                        return 'You are running the latest version of Orbital Editor Suite.';
                    case 'update-available':
                        return 'A new version is available. Update now to get the latest features and improvements.';
                    case 'checking':
                        return 'Please wait while we check for the latest version...';
                    default:
                        return 'Click "Check for Updates" to see if a new version is available.';
                }
            }
        },
        
        mounted() {
            this.loadInitialData();
        },
        
        methods: {
            /**
             * Load initial data from WordPress
             */
            loadInitialData() {
                if (typeof orbitalUpdatesVue !== 'undefined') {
                    this.currentVersion = orbitalUpdatesVue.current_version || '1.0.0';
                    this.lastChecked = orbitalUpdatesVue.plugin_info.last_checked || 'Never';
                    this.githubRepo = orbitalUpdatesVue.plugin_info.github_repo || 'orbital-design/orbital-editor-suite';
                    this.strings = orbitalUpdatesVue.strings || {};
                }
                
                // Load update history
                this.loadUpdateHistory();
                
                this.loading = false;
                
                // Auto-check for updates on page load
                setTimeout(() => {
                    this.checkForUpdates();
                }, 1000);
            },
            
            /**
             * Load update history
             */
            loadUpdateHistory() {
                // This would come from WordPress options in real implementation
                this.updateHistory = [
                    {
                        version: '1.0.0',
                        date: '2024-01-01',
                        status: 'Success'
                    }
                ];
            },
            
            /**
             * Check for updates
             */
            async checkForUpdates() {
                this.checking = true;
                this.updateStatus = 'checking';
                this.clearMessage();
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'orbital_updates_vue_check_version');
                    formData.append('nonce', orbitalUpdatesVue.nonce);
                    
                    const response = await fetch(orbitalUpdatesVue.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.latestVersion = data.data.latest_version;
                        this.updateAvailable = data.data.update_available;
                        this.lastChecked = data.data.last_checked;
                        
                        if (data.data.update_available) {
                            this.updateStatus = 'update-available';
                            this.updateInfo = data.data.update_info;
                            this.showMessage(this.strings.updateAvailable, 'warning');
                        } else {
                            this.updateStatus = 'up-to-date';
                            this.showMessage(this.strings.upToDate, 'success');
                        }
                    } else {
                        this.updateStatus = 'unknown';
                        this.showMessage(data.data || this.strings.error, 'error');
                    }
                } catch (error) {
                    console.error('Check updates error:', error);
                    this.updateStatus = 'unknown';
                    this.showMessage(this.strings.error, 'error');
                } finally {
                    this.checking = false;
                }
            },
            
            /**
             * Download and install update
             */
            async downloadUpdate() {
                if (!confirm('Are you sure you want to update the plugin? This will temporarily disable the plugin during the update process.')) {
                    return;
                }
                
                this.updating = true;
                this.clearMessage();
                this.showMessage(this.strings.downloading, 'info');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'orbital_updates_vue_download_update');
                    formData.append('nonce', orbitalUpdatesVue.nonce);
                    
                    const response = await fetch(orbitalUpdatesVue.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage(data.data.message, 'success');
                        this.currentVersion = data.data.new_version;
                        this.updateAvailable = false;
                        this.updateStatus = 'up-to-date';
                        this.updateInfo = null;
                        
                        // Refresh update history
                        this.loadUpdateHistory();
                        
                        // Suggest page refresh
                        setTimeout(() => {
                            if (confirm('Update completed! Would you like to refresh the page to see the changes?')) {
                                window.location.reload();
                            }
                        }, 2000);
                    } else {
                        this.showMessage(data.data || this.strings.updateFailed, 'error');
                    }
                } catch (error) {
                    console.error('Download update error:', error);
                    this.showMessage(this.strings.updateFailed, 'error');
                } finally {
                    this.updating = false;
                }
            },
            
            /**
             * Toggle auto updates
             */
            toggleAutoUpdates() {
                // This would save the setting in real implementation
                console.log('Auto updates:', this.autoUpdates ? 'enabled' : 'disabled');
                this.showMessage(
                    'Auto updates ' + (this.autoUpdates ? 'enabled' : 'disabled'),
                    'success'
                );
            },
            
            /**
             * Format date for display
             */
            formatDate(dateString) {
                if (!dateString || dateString === 'Never') return dateString;
                
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (error) {
                    return dateString;
                }
            },
            
            /**
             * Show message
             */
            showMessage(message, type = 'success') {
                this.message = message;
                this.messageType = type;
                
                // Auto-hide after 5 seconds for non-error messages
                if (type !== 'error') {
                    setTimeout(() => {
                        this.clearMessage();
                    }, 5000);
                }
            },
            
            /**
             * Clear message
             */
            clearMessage() {
                this.message = '';
            }
        }
    }).mount('#orbital-updates-vue-app');
});