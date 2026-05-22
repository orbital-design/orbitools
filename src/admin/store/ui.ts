/**
 * UI slice.
 *
 * Transient state — notices, modal stacks, etc. Notices use a simple
 * append-on-add / filter-on-remove pattern; @wordpress/notices could
 * replace this in a future iteration but keeping the surface small
 * for Phase 2.
 */
import type { Notice, UiState, State } from './state';

const initialState: UiState = {
    notices: [],
};

type Action =
    | { type: 'NOTICE_ADD'; notice: Notice }
    | { type: 'NOTICE_REMOVE'; id: string };

function reducer(state: UiState | undefined, action: { type: string; [key: string]: unknown }): UiState {
    const s = state ?? initialState;

    switch (action.type) {
        case 'NOTICE_ADD':
            return { ...s, notices: [...s.notices, action['notice'] as Notice] };

        case 'NOTICE_REMOVE': {
            const id = action['id'] as string;
            return { ...s, notices: s.notices.filter((n) => n.id !== id) };
        }

        default:
            return s;
    }
}

let noticeCounter = 0;
function nextNoticeId(): string {
    noticeCounter += 1;
    return `notice-${Date.now()}-${noticeCounter}`;
}

const actions = {
    addNotice: (notice: Omit<Notice, 'id'>): Action => ({
        type: 'NOTICE_ADD',
        notice: { ...notice, id: nextNoticeId() },
    }),
    removeNotice: (id: string): Action => ({ type: 'NOTICE_REMOVE', id }),
};

const selectors = {
    getNotices: (state: State): Notice[] => state.ui.notices,
};

export const uiSlice = {
    reducer,
    actions,
    selectors,
    resolvers: {},
};
