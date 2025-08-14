/**
 * Post Selector Component
 * 
 * A FormToken-based multiselect component for selecting posts by ID.
 * Loads all posts on mount and filters client-side for better performance.
 *
 * @file blocks/query-loop/components/PostSelector.tsx
 * @since 1.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FormTokenField, TextControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

interface Post {
    id: number;
    title: {
        rendered: string;
    };
    type: string;
    status: string;
}

interface PostSelectorProps {
    label: string;
    help?: string;
    value: string[];
    onChange: (postIds: string[]) => void;
    placeholder?: string;
    postTypes?: string[];
}

/**
 * Post Selector Component
 */
export default function PostSelector({
    label,
    help,
    value = [],
    onChange,
    placeholder,
    postTypes = []
}: PostSelectorProps) {
    const [allPosts, setAllPosts] = useState<{ id: string; title: string }[]>([]);
    const [filteredSuggestions, setFilteredSuggestions] = useState<{ id: string; title: string }[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [displayTokens, setDisplayTokens] = useState<string[]>([]);

    // Load all posts on mount or when post types change
    const loadAllPosts = async () => {
        console.log('=== loadAllPosts called ===');
        console.log('Post types:', postTypes);

        try {
            setIsLoading(true);
            
            const fetchPromises = [];

            // Determine which post types to load
            let typesToLoad = postTypes.length > 0 ? postTypes : [];
            
            // If no post types specified, get all available post types
            if (typesToLoad.length === 0) {
                try {
                    console.log('No post types specified, discovering available post types...');
                    const postTypesResponse = await apiFetch({
                        path: '/wp/v2/types'
                    }) as { [key: string]: any };

                    console.log('Raw post types response:', postTypesResponse);
                    
                    const availableTypes = Object.keys(postTypesResponse).filter(type => {
                        const postType = postTypesResponse[type];
                        console.log(`Checking post type "${type}":`, {
                            type,
                            viewable: postType.viewable,
                            publicly_queryable: postType.visibility?.publicly_queryable,
                            rest_base: postType.rest_base,
                            show_in_rest: postType.show_in_rest,
                            raw_post_type: postType
                        });
                        
                        // Since the properties are undefined, be more permissive
                        // Include post types that have a rest_base (indicating REST API support)
                        // Exclude system post types and 'post' type as requested
                        const systemTypes = ['nav_menu_item', 'wp_block', 'wp_template', 'wp_template_part', 
                                           'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face'];
                        
                        return type !== 'post' && 
                               !systemTypes.includes(type) && 
                               postType.rest_base && 
                               typeof postType.rest_base === 'string';
                    });
                    
                    console.log('Available post types:', availableTypes);
                    typesToLoad = availableTypes;
                } catch (error) {
                    console.error('Error fetching post types, defaulting to pages only:', error);
                    typesToLoad = ['page'];
                }
            }

            console.log('Loading posts for types:', typesToLoad);

            // Load each post type
            for (const postType of typesToLoad) {
                if (postType === 'page') {
                    console.log('Loading pages...');
                    fetchPromises.push(
                        apiFetch({
                            path: '/wp/v2/pages?per_page=100&_fields=id,title,type&status=publish'
                        }).then((posts: Post[]) => {
                            console.log(`Pages loaded: ${posts.length}`);
                            return posts.map(post => ({
                                id: post.id.toString(),
                                title: `${post.title.rendered} (ID: ${post.id})`
                            }));
                        }).catch(error => {
                            console.error('Error loading pages:', error);
                            return [];
                        })
                    );
                } else {
                    console.log(`Loading ${postType}...`);
                    // Try the specific post type endpoint first
                    fetchPromises.push(
                        apiFetch({
                            path: `/wp/v2/${postType}?per_page=100&_fields=id,title,type&status=publish`
                        }).then((posts: Post[]) => {
                            console.log(`${postType} loaded: ${posts.length}`);
                            return posts.map(post => ({
                                id: post.id.toString(),
                                title: `${post.title.rendered} (ID: ${post.id})`
                            }));
                        }).catch(error => {
                            console.warn(`Error loading ${postType} from direct endpoint:`, error);
                            // Fallback: try the generic posts endpoint with type filter
                            return apiFetch({
                                path: `/wp/v2/posts?per_page=100&type=${postType}&_fields=id,title,type&status=publish`
                            }).then((posts: Post[]) => {
                                console.log(`${postType} loaded via posts endpoint: ${posts.length}`);
                                return posts.map(post => ({
                                    id: post.id.toString(),
                                    title: `${post.title.rendered} (ID: ${post.id})`
                                }));
                            }).catch(fallbackError => {
                                console.error(`Error loading ${postType} via fallback:`, fallbackError);
                                return [];
                            });
                        })
                    );
                }
            }

            const results = await Promise.all(fetchPromises);
            const flatResults = results.flat();

            // Remove duplicates and sort by title
            const uniqueResults = flatResults.filter((result, index, self) => 
                index === self.findIndex(r => r.id === result.id)
            ).sort((a, b) => a.title.localeCompare(b.title));

            console.log('All posts loaded:', uniqueResults.length);
            setAllPosts(uniqueResults);
            setFilteredSuggestions(uniqueResults.slice(0, 20)); // Show first 20 initially

        } catch (error) {
            console.error('Error loading posts:', error);
            setAllPosts([]);
            setFilteredSuggestions([]);
        } finally {
            setIsLoading(false);
        }
    };

    // Filter posts based on search input (client-side)
    const filterPosts = (searchTerm: string) => {
        console.log('=== filterPosts called ===');
        console.log('Search term:', searchTerm);
        console.log('All posts available:', allPosts.length);

        if (!searchTerm || searchTerm.length < 1) {
            console.log('No search term, showing first 20 posts');
            setFilteredSuggestions(allPosts.slice(0, 20));
            return;
        }

        const filtered = allPosts.filter(post => 
            post.title.toLowerCase().includes(searchTerm.toLowerCase())
        ).slice(0, 20); // Limit to 20 suggestions

        console.log('Filtered results:', filtered.length);
        setFilteredSuggestions(filtered);
    };

    // Extract post IDs from display tokens
    const extractPostIds = (tokens: string[]): string[] => {
        return tokens.map(token => {
            const match = token.match(/\(ID: (\d+)\)$/);
            return match ? match[1] : token;
        }).filter(id => /^\d+$/.test(id)); // Only keep valid numeric IDs
    };

    // Convert post IDs to display tokens (title with ID)
    const getTokensFromValues = async (postIds: string[]) => {
        if (postIds.length === 0) return [];

        // First try to find in already loaded posts
        const foundTokens = postIds.map(id => {
            const post = allPosts.find(p => p.id === id);
            return post ? post.title : `Post ID: ${id}`;
        });

        setDisplayTokens(foundTokens);
        return foundTokens;
    };

    // Load posts when component mounts or post types change
    useEffect(() => {
        loadAllPosts();
    }, [postTypes.join(',')]);

    // Update display tokens when value changes
    useEffect(() => {
        if (value.length > 0 && allPosts.length > 0) {
            getTokensFromValues(value);
        } else {
            setDisplayTokens([]);
        }
    }, [value.join(','), allPosts.length]);

    return (
        <div>
            <FormTokenField
                label={label}
                help={help}
                value={displayTokens}
                suggestions={filteredSuggestions.map(s => s.title)}
                onChange={(tokens: string[]) => {
                    console.log('FormTokenField onChange called with tokens:', tokens);
                    const postIds = extractPostIds(tokens);
                    console.log('Extracted post IDs:', postIds);
                    onChange(postIds);
                }}
                onInputChange={(input: string) => {
                    console.log('FormTokenField onInputChange called with input:', input);
                    filterPosts(input);
                }}
                placeholder={placeholder || __('Search for posts...', 'orbitools')}
                __experimentalExpandOnFocus={true}
                __experimentalShowHowTo={false}
                __nextHasNoMarginBottom={true}
                maxSuggestions={20}
                disabled={isLoading}
            />
            
            {/* Debug info */}
            <div style={{ 
                fontSize: '11px', 
                color: '#666', 
                marginTop: '8px',
                padding: '4px',
                backgroundColor: '#f5f5f5',
                border: '1px solid #ddd'
            }}>
                <div>Loading: {isLoading ? 'Yes' : 'No'}</div>
                <div>Post Types: {postTypes.length > 0 ? postTypes.join(', ') : 'None (will search all)'}</div>
                <div>All Posts Loaded: {allPosts.length}</div>
                <div>Current Suggestions: {filteredSuggestions.length}</div>
                <div>Display Tokens: {displayTokens.length}</div>
                <div>Current Value: {JSON.stringify(value)}</div>
                {filteredSuggestions.length > 0 && (
                    <details style={{ marginTop: '4px' }}>
                        <summary>Suggestions Preview (click to expand)</summary>
                        <ul style={{ margin: '4px 0', paddingLeft: '16px', fontSize: '10px' }}>
                            {filteredSuggestions.slice(0, 5).map((suggestion, i) => (
                                <li key={i}>{suggestion.title}</li>
                            ))}
                            {filteredSuggestions.length > 5 && <li>... and {filteredSuggestions.length - 5} more</li>}
                        </ul>
                    </details>
                )}
            </div>
        </div>
    );
}