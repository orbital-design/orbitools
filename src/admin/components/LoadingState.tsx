/**
 * Centered spinner with optional caption. Used during async loads.
 */
import { Spinner } from '@wordpress/components';

interface LoadingStateProps {
    message?: string;
}

export function LoadingState({ message }: LoadingStateProps): JSX.Element {
    return (
        <div className="orbitools-loading">
            <Spinner />
            {message !== undefined && <p className="orbitools-loading__message">{message}</p>}
        </div>
    );
}
