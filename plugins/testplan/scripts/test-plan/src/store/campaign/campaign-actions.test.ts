/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { CampaignState } from "./type";
import { ActionContext } from "vuex";
import { RootState } from "../type";
import * as tlp from "tlp";
import { createCampaign, loadCampaigns, refreshCampaign } from "./campaign-actions";
import { Campaign } from "../../type";
import { mockFetchSuccess } from "../../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper";

jest.mock("tlp");

describe("Campaign state actions", () => {
    let context: ActionContext<CampaignState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;
    let tlpPostMock: jest.SpyInstance;
    let tlpGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                project_id: 104,
            } as RootState,
        } as unknown) as ActionContext<CampaignState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
        tlpPostMock = jest.spyOn(tlp, "post");
        tlpGetMock = jest.spyOn(tlp, "get");
    });

    describe("loadCampaigns", () => {
        it("Retrieves all campaigns for milestone", async () => {
            await loadCampaigns(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingCampaigns");
            expect(context.commit).toHaveBeenCalledWith("endLoadingCampaigns");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(
                `/api/v1/projects/104/testmanagement_campaigns`,
                {
                    params: { query: '{"milestone_id":42}', limit: 100 },
                    getCollectionCallback: expect.any(Function),
                }
            );
        });

        it("Catches error", async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);

            await expect(loadCampaigns(context)).rejects.toThrow();

            expect(context.commit).toHaveBeenCalledWith("beginLoadingCampaigns");
            expect(context.commit).toHaveBeenCalledWith("errorHasBeenCatched");
            expect(context.commit).toHaveBeenCalledWith("endLoadingCampaigns");
        });
    });

    describe("refreshCampaign", () => {
        it("Retrieves the new information about the campaign", async () => {
            mockFetchSuccess(tlpGetMock, {
                return_json: { id: 123 },
            });

            const campaign = { id: 123 } as Campaign;
            await refreshCampaign(context, campaign);

            expect(tlpGetMock).toHaveBeenCalledWith(`/api/v1/testmanagement_campaigns/123`);
        });

        it("Commits the new information about the campaign", async () => {
            mockFetchSuccess(tlpGetMock, {
                return_json: {
                    id: 123,
                    nb_of_blocked: 1,
                    nb_of_passed: 2,
                    nb_of_notrun: 3,
                    nb_of_failed: 4,
                },
            });

            const campaign = { id: 123 } as Campaign;
            await refreshCampaign(context, campaign);

            expect(context.commit).toHaveBeenCalledWith("updateCampaignAfterCreation", {
                id: 123,
                nb_of_blocked: 1,
                nb_of_passed: 2,
                nb_of_notrun: 3,
                nb_of_failed: 4,
            });
        });
    });

    describe("createCampaign", () => {
        it("Post information to create a new campaign", async () => {
            mockFetchSuccess(tlpPostMock, {
                return_json: { id: 123 },
            });

            await createCampaign(context, { label: "New campaign", test_selector: "milestone" });

            expect(tlpPostMock).toHaveBeenCalledWith(
                `/api/v1/testmanagement_campaigns?milestone_id=42&test_selector=milestone`,
                {
                    body: JSON.stringify({ project_id: 104, label: "New campaign" }),
                    headers: { "content-type": "application/json" },
                }
            );
        });

        it("Commits as soon as possible the new campaign, and asks to refresh it", async () => {
            mockFetchSuccess(tlpPostMock, {
                return_json: { id: 123 },
            });

            await createCampaign(context, { label: "New campaign", test_selector: "milestone" });

            const campaign = {
                id: 123,
                label: "New campaign",
                nb_of_notrun: 0,
                nb_of_blocked: 0,
                nb_of_failed: 0,
                nb_of_passed: 0,
                is_being_refreshed: true,
                is_just_refreshed: false,
            };
            expect(context.commit).toHaveBeenCalledWith("addNewCampaign", campaign);
            expect(context.dispatch).toHaveBeenCalledWith("refreshCampaign", campaign);
        });
    });
});
