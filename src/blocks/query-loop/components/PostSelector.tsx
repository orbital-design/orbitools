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
        try {
            setIsLoading(true);
            
            const fetchPromises = [];

            // Determine which post types to load
            let typesToLoad = postTypes.length > 0 ? postTypes : [];
            
            // If no post types specified, get all available post types
            if (typesToLoad.length === 0) {
                try {
                    const postTypesResponse = await apiFetch({
                        path: '/wp/v2/types'
                    }) as { [key: string]: any };
                    
                    const availableTypes = Object.keys(postTypesResponse).filter(type => {
                        const postType = postTypesResponse[type];
                        
                        // Include post types that have a rest_base (indicating REST API support)
                        // Exclude system post types and 'post' type as requested
                        const systemTypes = ['nav_menu_item', 'wp_block', 'wp_template', 'wp_template_part', 
                                           'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face'];
                        
                        return type !== 'post' && 
                               !systemTypes.includes(type) && 
                               postType.rest_base && 
                               typeof postType.rest_base === 'string';
                    });
                    
                    typesToLoad = availableTypes;
                } catch (error) {
                    typesToLoad = ['page'];
                }
            }

            // Load each post type
            for (const postType of typesToLoad) {
                if (postType === 'page') {
                    fetchPromises.push(
                        apiFetch({
                            path: '/wp/v2/pages?per_page=100&_fields=id,title,type&status=publish'
                        }).then((posts: Post[]) => {
                            return posts.map(post => ({
                                id: post.id.toString(),
                                title: `${post.title.rendered} (ID: ${post.id})`
                            }));
                        }).catch(() => [])
                    );
                } else {
                    // Try the specific post type endpoint first
                    fetchPromises.push(
                        apiFetch({
                            path: `/wp/v2/${postType}?per_page=100&_fields=id,title,type&status=publish`
                        }).then((posts: Post[]) => {
                            return posts.map(post => ({
                                id: post.id.toString(),
                                title: `${post.title.rendered} (ID: ${post.id})`
                            }));
                        }).catch(() => {
                            // Fallback: try the generic posts endpoint with type filter
                            return apiFetch({
                                path: `/wp/v2/posts?per_page=100&type=${postType}&_fields=id,title,type&status=publish`
                            }).then((posts: Post[]) => {
                                return posts.map(post => ({
                                    id: post.id.toString(),
                                    title: `${post.title.rendered} (ID: ${post.id})`
                                }));
                            }).catch(() => []);
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

            setAllPosts(uniqueResults);
            setFilteredSuggestions(uniqueResults.slice(0, 20)); // Show first 20 initially

        } catch (error) {
            setAllPosts([]);
            setFilteredSuggestions([]);
        } finally {
            setIsLoading(false);
        }
    };

    // Filter posts based on search input (client-side)
    const filterPosts = (searchTerm: string) => {
        if (!searchTerm || searchTerm.length < 1) {
            setFilteredSuggestions(allPosts.slice(0, 20));
            return;
        }

        const filtered = allPosts.filter(post => 
            post.title.toLowerCase().includes(searchTerm.toLowerCase())
        ).slice(0, 20); // Limit to 20 suggestions

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
                    const postIds = extractPostIds(tokens);
                    onChange(postIds);
                }}
                onInputChange={(input: string) => {
                    filterPosts(input);
                }}
                placeholder={placeholder || __('Search for posts...', 'orbitools')}
                __experimentalExpandOnFocus={true}
                __experimentalShowHowTo={false}
                __nextHasNoMarginBottom={true}
                maxSuggestions={20}
                disabled={isLoading}
            />
            
        </div>
    );
}